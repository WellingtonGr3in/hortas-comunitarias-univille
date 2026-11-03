<?php

namespace App\Services;

use App\Exceptions\AcessoNegadoException;
use App\Exceptions\RecursoNaoEncontradoException;
use App\Models\PagamentoModel;
use App\Repositories\PagamentoRepository;
use App\Repositories\CanteiristaRepository;
use App\Repositories\HortaRepository;
use App\Repositories\MensalidadeDaAssociacaoRepository;
use App\Repositories\MensalidadeDaPlataformaRepository;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Quem pode o quê:
 * - admin_plataforma: todos os pagamentos.
 * - admin_associacao_geral: pagamentos das mensalidades da sua associação e dos canteiristas das hortas dela.
 * - admin_horta_geral: pagamentos dos canteiristas da sua horta.
 * - canteirista: só consulta os próprios pagamentos.
 * Lançar e editar pagamento avulso é só para os cargos de administração.
 * Pagamento de mensalidade e estorno seguem assertPodeLancarPagamento.
 * Falta de permissão lança AcessoNegadoException (403).
 */
class PagamentoService
{
    // Códigos de status das mensalidades (docs/db/README.md)
    private const STATUS_AGUARDANDO = 0;
    private const STATUS_PAGO = 1;
    private const STATUS_COMPENSADO = 2;
    private const STATUS_CANCELADO = 3;

    private const CARGOS_ADMIN = ['admin_plataforma', 'admin_associacao_geral', 'admin_horta_geral'];

    private PagamentoRepository $repository;
    private CanteiristaRepository $CanteiristaRepository;
    private HortaRepository $hortaRepository;
    private CargoService $cargoService;

    public function __construct(
        PagamentoRepository $repository,
        CanteiristaRepository $CanteiristaRepository,
        HortaRepository $hortaRepository,
        CargoService $cargoService,
        private MensalidadeDaAssociacaoRepository $mensalidadeDaAssociacaoRepository,
        private MensalidadeDaPlataformaRepository $mensalidadeDaPlataformaRepository
    ) {
        $this->repository = $repository;
        $this->CanteiristaRepository = $CanteiristaRepository;
        $this->hortaRepository = $hortaRepository;
        $this->cargoService = $cargoService;
    }

    public function findAll(array $payloadUsuarioLogado)
    {
        switch ($this->getCargoSlug($payloadUsuarioLogado)) {
            case 'admin_plataforma':
                return $this->repository->findAll();

            case 'admin_associacao_geral':
                return $this->repository->findAllDaAssociacao(
                    $payloadUsuarioLogado['associacao_uuid'],
                    $this->hortasDoEscopo($payloadUsuarioLogado)
                );

            case 'admin_horta_geral':
                return $this->repository->findAllDasHortas($this->hortasDoEscopo($payloadUsuarioLogado));

            case 'canteirista':
                return $this->repository->findAllDoUsuario($payloadUsuarioLogado['usuario_uuid']);

            default:
                throw new AcessoNegadoException('Você não tem permissão para ver os pagamentos');
        }
    }

    /**
     * Registra o pagamento de uma mensalidade em aberto e a quita, tudo na mesma transação.
     * $data: mensalidade_tipo (associacao|plataforma), mensalidade_uuid, valor, forma_pagamento,
     *        data_pagamento (opcional, padrão hoje), observacao (opcional)
     */
    public function registrarPagamentoDeMensalidade(array $data, array $payloadUsuarioLogado): PagamentoModel
    {
        $tipo = $data['mensalidade_tipo'] ?? null;
        if (!in_array($tipo, ['associacao', 'plataforma'], true)) {
            throw new InvalidArgumentException('Tipo de mensalidade inválido (associacao ou plataforma)');
        }
        if (empty($data['mensalidade_uuid'])) {
            throw new InvalidArgumentException('Mensalidade é obrigatória');
        }
        if (!is_numeric($data['valor'] ?? null) || $data['valor'] <= 0) {
            throw new InvalidArgumentException('Valor deve ser maior que zero');
        }
        if (!in_array($data['forma_pagamento'] ?? null, ['dinheiro', 'pix'], true)) {
            throw new InvalidArgumentException('Forma de pagamento deve ser dinheiro ou pix');
        }
        $dataPagamento = $data['data_pagamento'] ?? date('Y-m-d');
        $dataValida = \DateTime::createFromFormat('!Y-m-d', (string) $dataPagamento);
        if (!$dataValida || $dataValida->format('Y-m-d') !== $dataPagamento) {
            throw new InvalidArgumentException('Data do pagamento inválida (use AAAA-MM-DD)');
        }
        if ($dataPagamento > date('Y-m-d')) {
            throw new InvalidArgumentException('Data do pagamento não pode ser no futuro');
        }

        $usuarioLogado = $payloadUsuarioLogado['usuario_uuid'];

        return DB::connection()->transaction(function () use ($data, $tipo, $dataPagamento, $usuarioLogado, $payloadUsuarioLogado) {
            $mensalidade = $this->buscarMensalidadeParaAtualizar($tipo, $data['mensalidade_uuid']);
            $this->assertPodeLancarPagamento($tipo, $mensalidade, $payloadUsuarioLogado);

            $status = (int) $mensalidade->status;
            if ($status === self::STATUS_PAGO || $status === self::STATUS_COMPENSADO) {
                throw new InvalidArgumentException('Esta mensalidade já está paga. Para lançar de novo, estorne o pagamento anterior.');
            }
            if ($status === self::STATUS_CANCELADO) {
                throw new InvalidArgumentException('Mensalidade cancelada não pode receber pagamento');
            }
            if ($this->repository->findAtivoPorMensalidade($tipo, $mensalidade->uuid)) {
                throw new InvalidArgumentException('Esta mensalidade já tem um pagamento registrado. Para lançar de novo, estorne o pagamento anterior.');
            }

            $canteirista = $tipo === 'associacao'
                ? $this->CanteiristaRepository->findByUsuarioUuid($mensalidade->usuario_uuid)
                : null;

            $pagamento = $this->repository->create([
                'carteirista_uuid' => $canteirista?->uuid,
                'mensalidade_uuid' => $mensalidade->uuid,
                'mensalidade_tipo' => $tipo,
                'valor' => $data['valor'],
                'forma_pagamento' => $data['forma_pagamento'],
                'data_pagamento' => $dataPagamento,
                'observacao' => $data['observacao'] ?? null,
                'excluido' => 0,
                'usuario_criador_uuid' => $usuarioLogado,
                'usuario_alterador_uuid' => $usuarioLogado,
            ]);

            $mensalidade->update([
                'status' => self::STATUS_PAGO,
                'data_pagamento' => $dataPagamento,
                'usuario_alterador_uuid' => $usuarioLogado,
            ]);

            return $this->repository->findByUuid($pagamento->uuid);
        });
    }

    /**
     * Estorno: exclusão lógica do pagamento. Se ele quitava uma mensalidade, ela volta para em aberto.
     */
    public function estornar(string $uuid, array $payloadUsuarioLogado): void
    {
        $usuarioLogado = $payloadUsuarioLogado['usuario_uuid'];

        DB::connection()->transaction(function () use ($uuid, $usuarioLogado, $payloadUsuarioLogado) {
            $pagamento = $this->repository->findByUuid($uuid);
            if (!$pagamento) {
                throw new RecursoNaoEncontradoException('Pagamento não encontrado');
            }

            $mensalidade = null;
            if ($pagamento->mensalidade_tipo) {
                $mensalidade = $this->buscarMensalidadeParaAtualizar($pagamento->mensalidade_tipo, $pagamento->mensalidade_uuid);
                $this->assertPodeLancarPagamento($pagamento->mensalidade_tipo, $mensalidade, $payloadUsuarioLogado);
            } elseif ($this->getCargoSlug($payloadUsuarioLogado) !== 'admin_plataforma') {
                // Pagamento avulso (anterior à ligação com mensalidade): sem associação definida
                throw new AcessoNegadoException('Só o administrador da plataforma pode estornar pagamentos avulsos');
            }

            $pagamento->update(['excluido' => 1, 'usuario_alterador_uuid' => $usuarioLogado]);

            $mensalidade?->update([
                'status' => self::STATUS_AGUARDANDO,
                'data_pagamento' => null,
                'usuario_alterador_uuid' => $usuarioLogado,
            ]);
        });
    }

    private function buscarMensalidadeParaAtualizar(string $tipo, string $mensalidadeUuid): Model
    {
        $mensalidade = $tipo === 'associacao'
            ? $this->mensalidadeDaAssociacaoRepository->findByUuidParaAtualizar($mensalidadeUuid)
            : $this->mensalidadeDaPlataformaRepository->findByUuidParaAtualizar($mensalidadeUuid);

        if (!$mensalidade || $mensalidade->excluido) {
            throw new RecursoNaoEncontradoException('Mensalidade não encontrada');
        }
        return $mensalidade;
    }

    // admin_plataforma: mensalidades da plataforma; admin_associacao_geral: mensalidades da própria associação
    private function assertPodeLancarPagamento(string $tipo, Model $mensalidade, array $payloadUsuarioLogado): void
    {
        $cargo = $this->getCargoSlug($payloadUsuarioLogado);

        $permitido = match ($tipo) {
            'plataforma' => $cargo === 'admin_plataforma',
            'associacao' => $cargo === 'admin_associacao_geral'
                && $mensalidade->associacao_uuid === $payloadUsuarioLogado['associacao_uuid'],
        };

        if (!$permitido) {
            throw new AcessoNegadoException('Você não tem permissão para lançar ou estornar pagamento desta mensalidade');
        }
    }

    // cargo_slug vem no JWT; tokens emitidos antes dessa mudança não têm, então busca pelo cargo_uuid
    private function getCargoSlug(array $payloadUsuarioLogado): string
    {
        return $payloadUsuarioLogado['cargo_slug']
            ?? $this->cargoService->findByUuidInternal($payloadUsuarioLogado['cargo_uuid'])->slug;
    }

    public function findByUuid(string $uuid, array $payloadUsuarioLogado)
    {
        $pagamento = $this->repository->findByUuid($uuid);
        if (!$pagamento) {
            throw new InvalidArgumentException('Pagamento não encontrado');
        }

        $cargo = $this->getCargoSlug($payloadUsuarioLogado);
        if ($cargo === 'canteirista') {
            if ($pagamento->Canteirista?->usuario_uuid !== $payloadUsuarioLogado['usuario_uuid']) {
                throw new InvalidArgumentException('Pagamento não encontrado');
            }
            return $pagamento;
        }

        $this->verificarCanteiristaNoEscopo($pagamento->Canteirista, $payloadUsuarioLogado);
        return $pagamento;
    }

    public function create(array $data, array $payloadUsuarioLogado)
    {
        $this->verificarCargoAdmin($payloadUsuarioLogado);

        // Validações
        if (empty($data['carteirista_uuid'])) {
            throw new InvalidArgumentException('Canteirista é obrigatório');
        }

        if (empty($data['valor']) || $data['valor'] <= 0) {
            throw new InvalidArgumentException('Valor deve ser maior que zero');
        }

        if (empty($data['forma_pagamento'])) {
            throw new InvalidArgumentException('Forma de pagamento é obrigatória');
        }

        if (!in_array($data['forma_pagamento'], ['dinheiro', 'pix'])) {
            throw new InvalidArgumentException('Forma de pagamento inválida');
        }

        // Verifica se Canteirista existe e se é do escopo de quem lança
        $Canteirista = $this->CanteiristaRepository->findByUuid($data['carteirista_uuid']);
        if (!$Canteirista) {
            throw new InvalidArgumentException('Canteirista não encontrado');
        }
        $this->verificarCanteiristaNoEscopo($Canteirista, $payloadUsuarioLogado);

        // Define data_pagamento como hoje se não informada
        if (empty($data['data_pagamento'])) {
            $data['data_pagamento'] = date('Y-m-d');
        }

        $data['usuario_criador_uuid'] = $payloadUsuarioLogado['usuario_uuid'];
        $data['usuario_alterador_uuid'] = $payloadUsuarioLogado['usuario_uuid'];

        return $this->repository->create($data);
    }

    public function update(string $uuid, array $data, array $payloadUsuarioLogado)
    {
        $this->verificarCargoAdmin($payloadUsuarioLogado);
        $this->findByUuid($uuid, $payloadUsuarioLogado);

        // Validações
        if (isset($data['carteirista_uuid']) && !empty($data['carteirista_uuid'])) {
            $Canteirista = $this->CanteiristaRepository->findByUuid($data['carteirista_uuid']);
            if (!$Canteirista) {
                throw new InvalidArgumentException('Canteirista não encontrado');
            }
            $this->verificarCanteiristaNoEscopo($Canteirista, $payloadUsuarioLogado);
        }

        if (isset($data['valor']) && $data['valor'] <= 0) {
            throw new InvalidArgumentException('Valor deve ser maior que zero');
        }

        if (isset($data['forma_pagamento']) && !in_array($data['forma_pagamento'], ['dinheiro', 'pix'])) {
            throw new InvalidArgumentException('Forma de pagamento inválida');
        }

        foreach (['uuid', 'excluido', 'usuario_criador_uuid', 'data_de_criacao'] as $g) unset($data[$g]);
        $data['usuario_alterador_uuid'] = $payloadUsuarioLogado['usuario_uuid'];

        $pagamento = $this->repository->update($uuid, $data);
        if (!$pagamento) {
            throw new InvalidArgumentException('Pagamento não encontrado');
        }

        return $pagamento;
    }

    private function verificarCargoAdmin(array $payloadUsuarioLogado): void
    {
        if (!in_array($this->getCargoSlug($payloadUsuarioLogado), self::CARGOS_ADMIN, true)) {
            throw new AcessoNegadoException('Somente administradores podem lançar, editar ou excluir pagamentos');
        }
    }

    private function verificarCanteiristaNoEscopo($Canteirista, array $payloadUsuarioLogado): void
    {
        $cargo = $this->getCargoSlug($payloadUsuarioLogado);
        if ($cargo === 'admin_plataforma') return;

        if (!$Canteirista || !in_array($Canteirista->horta_uuid, $this->hortasDoEscopo($payloadUsuarioLogado), true)) {
            throw new AcessoNegadoException('Este canteirista não é da sua associação ou horta');
        }
    }

    // Hortas que o admin enxerga: a própria (admin de horta) ou todas da associação (admin de associação).
    private function hortasDoEscopo(array $payloadUsuarioLogado): array
    {
        $cargo = $this->getCargoSlug($payloadUsuarioLogado);

        if ($cargo === 'admin_horta_geral') {
            return array_filter([$payloadUsuarioLogado['horta_uuid'] ?? null]);
        }
        if ($cargo === 'admin_associacao_geral') {
            return $this->hortaRepository->findAllWhere([
                'associacao_vinculada_uuid' => $payloadUsuarioLogado['associacao_uuid'],
                'excluido' => 0,
            ])->pluck('uuid')->all();
        }
        return [];
    }
}
