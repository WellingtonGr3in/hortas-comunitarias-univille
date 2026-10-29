<?php

namespace App\Services;

use App\Exceptions\AcessoNegadoException;
use App\Exceptions\RecursoNaoEncontradoException;
use App\Models\PagamentoModel;
use App\Repositories\PagamentoRepository;
use App\Repositories\CanteiristaRepository;
use App\Repositories\MensalidadeDaAssociacaoRepository;
use App\Repositories\MensalidadeDaPlataformaRepository;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class PagamentoService
{
    // Códigos de status das mensalidades (docs/db/README.md)
    private const STATUS_AGUARDANDO = 0;
    private const STATUS_PAGO = 1;
    private const STATUS_COMPENSADO = 2;
    private const STATUS_CANCELADO = 3;

    private PagamentoRepository $repository;
    private CanteiristaRepository $CanteiristaRepository;

    public function __construct(
        PagamentoRepository $repository,
        CanteiristaRepository $CanteiristaRepository,
        private MensalidadeDaAssociacaoRepository $mensalidadeDaAssociacaoRepository,
        private MensalidadeDaPlataformaRepository $mensalidadeDaPlataformaRepository,
        private CargoService $cargoService
    ) {
        $this->repository = $repository;
        $this->CanteiristaRepository = $CanteiristaRepository;
    }

    public function findAll(array $payloadUsuarioLogado)
    {
        return match ($this->getCargoSlug($payloadUsuarioLogado)) {
            'admin_plataforma' => $this->repository->findAll(),
            'admin_associacao_geral' => $this->repository->findAllDaAssociacao($payloadUsuarioLogado['associacao_uuid']),
            default => throw new AcessoNegadoException('Você não tem permissão para ver os pagamentos'),
        };
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

    public function findByUuid(string $uuid)
    {
        $pagamento = $this->repository->findByUuid($uuid);
        if (!$pagamento) {
            throw new InvalidArgumentException('Pagamento não encontrado');
        }
        return $pagamento;
    }

    public function create(array $data)
    {
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

        // Verifica se Canteirista existe
        $Canteirista = $this->CanteiristaRepository->findByUuid($data['carteirista_uuid']);
        if (!$Canteirista) {
            throw new InvalidArgumentException('Canteirista não encontrado');
        }

        // Define data_pagamento como hoje se não informada
        if (empty($data['data_pagamento'])) {
            $data['data_pagamento'] = date('Y-m-d');
        }

        return $this->repository->create($data);
    }

    public function update(string $uuid, array $data)
    {
        // Validações
        if (isset($data['carteirista_uuid']) && !empty($data['carteirista_uuid'])) {
            $Canteirista = $this->CanteiristaRepository->findByUuid($data['carteirista_uuid']);
            if (!$Canteirista) {
                throw new InvalidArgumentException('Canteirista não encontrado');
            }
        }

        if (isset($data['valor']) && $data['valor'] <= 0) {
            throw new InvalidArgumentException('Valor deve ser maior que zero');
        }

        if (isset($data['forma_pagamento']) && !in_array($data['forma_pagamento'], ['dinheiro', 'pix'])) {
            throw new InvalidArgumentException('Forma de pagamento inválida');
        }

        $pagamento = $this->repository->update($uuid, $data);
        if (!$pagamento) {
            throw new InvalidArgumentException('Pagamento não encontrado');
        }

        return $pagamento;
    }

    public function delete(string $uuid)
    {
        $deleted = $this->repository->delete($uuid);
        if (!$deleted) {
            throw new InvalidArgumentException('Pagamento não encontrado');
        }
        return true;
    }
}

