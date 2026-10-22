<?php

namespace App\Services;

use App\Exceptions\AcessoNegadoException;
use App\Repositories\NotificacaoRepository;
use App\Repositories\CanteiristaRepository;
use App\Repositories\HortaRepository;
use InvalidArgumentException;

/**
 * Quem vê o quê:
 * - aviso_geral: todo mundo.
 * - evento_horta: quem tem a horta no escopo (admin da plataforma, admin da associação da horta, admin e canteiristas da horta).
 * - aviso_canteirista: o próprio canteirista e os admins que têm a horta dele no escopo.
 * Quem cria/edita/exclui:
 * - Aviso Geral: só o admin da plataforma.
 * - Evento da Horta e aviso para canteirista: admins, dentro do próprio escopo.
 */
class NotificacaoService
{
    private const CARGOS_ADMIN = ['admin_plataforma', 'admin_associacao_geral', 'admin_horta_geral'];

    private NotificacaoRepository $repository;
    private CanteiristaRepository $CanteiristaRepository;
    private HortaRepository $hortaRepository;
    private CargoService $cargoService;

    public function __construct(
        NotificacaoRepository $repository,
        CanteiristaRepository $CanteiristaRepository,
        HortaRepository $hortaRepository,
        CargoService $cargoService
    ) {
        $this->repository = $repository;
        $this->CanteiristaRepository = $CanteiristaRepository;
        $this->hortaRepository = $hortaRepository;
        $this->cargoService = $cargoService;
    }

    public function findAll(array $payloadUsuarioLogado)
    {
        return $this->filtrarVisiveis($this->repository->findAll(), $payloadUsuarioLogado);
    }

    public function findAtivas(array $payloadUsuarioLogado)
    {
        return $this->filtrarVisiveis($this->repository->findAtivas(), $payloadUsuarioLogado);
    }

    public function findUuidsLidas(string $usuarioUuid): array
    {
        return $this->repository->findUuidsLidasPorUsuario($usuarioUuid);
    }

    public function marcarComoLida(string $uuid, array $payloadUsuarioLogado): void
    {
        $this->findByUuid($uuid, $payloadUsuarioLogado);
        $this->repository->marcarComoLida($uuid, $payloadUsuarioLogado['usuario_uuid']);
    }

    public function marcarTodasComoLidas(array $payloadUsuarioLogado): int
    {
        $uuids = $this->findAll($payloadUsuarioLogado)->pluck('uuid')->all();
        return $this->repository->marcarVariasComoLidas($uuids, $payloadUsuarioLogado['usuario_uuid']);
    }

    public function findByUuid(string $uuid, array $payloadUsuarioLogado)
    {
        $notificacao = $this->repository->findByUuid($uuid);
        if (!$notificacao) {
            throw new InvalidArgumentException('Notificação não encontrada');
        }

        $escopo = $this->escopo($payloadUsuarioLogado);
        if (!$this->podeVer($notificacao, $escopo, $payloadUsuarioLogado)) {
            throw new AcessoNegadoException('Você não tem permissão para acessar esta notificação');
        }
        return $notificacao;
    }

    public function create(array $data, array $payloadUsuarioLogado)
    {
        // Validações
        if (empty($data['tipo'])) {
            throw new InvalidArgumentException('Tipo é obrigatório');
        }

        if (!in_array($data['tipo'], ['aviso_geral', 'aviso_canteirista', 'evento_horta'])) {
            throw new InvalidArgumentException('Tipo inválido');
        }

        if (empty($data['titulo'])) {
            throw new InvalidArgumentException('Título é obrigatório');
        }

        if (empty($data['mensagem'])) {
            throw new InvalidArgumentException('Mensagem é obrigatória');
        }

        // Validações específicas por tipo
        if ($data['tipo'] === 'aviso_canteirista') {
            if (empty($data['carteirista_uuid'])) {
                throw new InvalidArgumentException('Canteirista é obrigatório para aviso específico');
            }

            $Canteirista = $this->CanteiristaRepository->findByUuid($data['carteirista_uuid']);
            if (!$Canteirista) {
                throw new InvalidArgumentException('Canteirista não encontrado');
            }
        }

        if ($data['tipo'] === 'evento_horta') {
            if (empty($data['horta_uuid'])) {
                throw new InvalidArgumentException('Horta é obrigatória para evento');
            }

            $horta = $this->hortaRepository->findByUuid($data['horta_uuid']);
            if (!$horta) {
                throw new InvalidArgumentException('Horta não encontrada');
            }
        }

        $this->verificarPodeGerir($data, $payloadUsuarioLogado);

        // Define data_inicio como agora se não informada
        if (empty($data['data_inicio'])) {
            $data['data_inicio'] = date('Y-m-d H:i:s');
        }

        // Define prioridade como média se não informada
        if (empty($data['prioridade'])) {
            $data['prioridade'] = 'media';
        }

        // Define ativa como true se não informada
        if (!isset($data['ativa'])) {
            $data['ativa'] = 1;
        }

        $data['usuario_criador_uuid'] = $payloadUsuarioLogado['usuario_uuid'];
        $data['usuario_alterador_uuid'] = $payloadUsuarioLogado['usuario_uuid'];

        return $this->repository->create($data);
    }

    public function update(string $uuid, array $data, array $payloadUsuarioLogado)
    {
        $atual = $this->findByUuid($uuid, $payloadUsuarioLogado);
        $this->verificarPodeGerir($atual->toArray(), $payloadUsuarioLogado);

        // Validações específicas se tipo for alterado
        if (isset($data['tipo'])) {
            if (!in_array($data['tipo'], ['aviso_geral', 'aviso_canteirista', 'evento_horta'])) {
                throw new InvalidArgumentException('Tipo inválido');
            }
        }

        if (isset($data['carteirista_uuid']) && !empty($data['carteirista_uuid'])) {
            $Canteirista = $this->CanteiristaRepository->findByUuid($data['carteirista_uuid']);
            if (!$Canteirista) {
                throw new InvalidArgumentException('Canteirista não encontrado');
            }
        }

        if (isset($data['horta_uuid']) && !empty($data['horta_uuid'])) {
            $horta = $this->hortaRepository->findByUuid($data['horta_uuid']);
            if (!$horta) {
                throw new InvalidArgumentException('Horta não encontrada');
            }
        }

        // A notificação como vai ficar também precisa estar no escopo de quem edita
        $this->verificarPodeGerir(array_merge($atual->toArray(), $data), $payloadUsuarioLogado);

        foreach (['uuid', 'excluido', 'usuario_criador_uuid', 'data_de_criacao'] as $g) unset($data[$g]);
        $data['usuario_alterador_uuid'] = $payloadUsuarioLogado['usuario_uuid'];

        $notificacao = $this->repository->update($uuid, $data);
        if (!$notificacao) {
            throw new InvalidArgumentException('Notificação não encontrada');
        }

        return $notificacao;
    }

    public function delete(string $uuid, array $payloadUsuarioLogado)
    {
        $atual = $this->findByUuid($uuid, $payloadUsuarioLogado);
        $this->verificarPodeGerir($atual->toArray(), $payloadUsuarioLogado);

        $deleted = $this->repository->delete($uuid);
        if (!$deleted) {
            throw new InvalidArgumentException('Notificação não encontrada');
        }
        return true;
    }

    private function filtrarVisiveis($notificacoes, array $payloadUsuarioLogado)
    {
        $escopo = $this->escopo($payloadUsuarioLogado);
        return $notificacoes->filter(fn($n) => $this->podeVer($n, $escopo, $payloadUsuarioLogado))->values();
    }

    private function podeVer($notificacao, array $escopo, array $payloadUsuarioLogado): bool
    {
        if ($escopo['cargo'] === 'admin_plataforma' || $notificacao->tipo === 'aviso_geral') {
            return true;
        }

        if ($notificacao->tipo === 'evento_horta') {
            return in_array($notificacao->horta_uuid, $escopo['hortas'], true);
        }

        // aviso_canteirista
        $Canteirista = $notificacao->Canteirista;
        if (!$Canteirista) {
            return false;
        }
        if ($escopo['cargo'] === 'canteirista') {
            return $Canteirista->usuario_uuid === $payloadUsuarioLogado['usuario_uuid'];
        }
        return in_array($Canteirista->horta_uuid, $escopo['hortas'], true);
    }

    // $dados: campos da notificação (tipo, horta_uuid, carteirista_uuid)
    private function verificarPodeGerir(array $dados, array $payloadUsuarioLogado): void
    {
        $escopo = $this->escopo($payloadUsuarioLogado);
        $cargo = $escopo['cargo'];

        if (!in_array($cargo, self::CARGOS_ADMIN, true)) {
            throw new AcessoNegadoException('Somente administradores podem criar, editar ou excluir notificações');
        }
        if ($cargo === 'admin_plataforma') {
            return;
        }

        $tipo = $dados['tipo'] ?? null;
        if ($tipo === 'aviso_geral') {
            throw new AcessoNegadoException('Somente o administrador da plataforma pode enviar Aviso Geral');
        }
        if ($tipo === 'evento_horta' && !in_array($dados['horta_uuid'] ?? null, $escopo['hortas'], true)) {
            throw new AcessoNegadoException('Você só pode criar eventos para hortas do seu escopo');
        }
        if ($tipo === 'aviso_canteirista') {
            $Canteirista = $this->CanteiristaRepository->findByUuid($dados['carteirista_uuid'] ?? '');
            if (!$Canteirista || !in_array($Canteirista->horta_uuid, $escopo['hortas'], true)) {
                throw new AcessoNegadoException('Você só pode avisar canteiristas do seu escopo');
            }
        }
    }

    // Cargo do usuário e as hortas que ele enxerga
    private function escopo(array $payloadUsuarioLogado): array
    {
        $cargo = $this->cargoService->findByUuidInternal($payloadUsuarioLogado['cargo_uuid'])->slug;

        $hortas = [];
        if ($cargo === 'admin_associacao_geral') {
            $hortas = $this->hortaRepository->findAllWhere([
                'associacao_vinculada_uuid' => $payloadUsuarioLogado['associacao_uuid'],
                'excluido' => 0,
            ])->pluck('uuid')->all();
        } elseif (in_array($cargo, ['admin_horta_geral', 'canteirista'], true)) {
            $hortas = array_filter([$payloadUsuarioLogado['horta_uuid'] ?? null]);
        }

        return ['cargo' => $cargo, 'hortas' => $hortas];
    }
}
