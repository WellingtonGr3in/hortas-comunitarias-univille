<?php

namespace App\Services;

use App\Repositories\PagamentoRepository;
use App\Repositories\CanteiristaRepository;
use App\Repositories\HortaRepository;
use DomainException;
use InvalidArgumentException;

/**
 * Quem pode o quê:
 * - admin_plataforma: todos os pagamentos.
 * - admin_associacao_geral: pagamentos dos canteiristas das hortas da sua associação.
 * - admin_horta_geral: pagamentos dos canteiristas da sua horta.
 * - canteirista: só consulta os próprios pagamentos.
 * Lançar, editar e excluir é só para os cargos de administração.
 * Falta de permissão lança DomainException, que o controller devolve como 403.
 */
class PagamentoService
{
    private const CARGOS_ADMIN = ['admin_plataforma', 'admin_associacao_geral', 'admin_horta_geral'];

    private PagamentoRepository $repository;
    private CanteiristaRepository $CanteiristaRepository;
    private HortaRepository $hortaRepository;
    private CargoService $cargoService;

    public function __construct(
        PagamentoRepository $repository,
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
        switch ($this->getCargoSlug($payloadUsuarioLogado)) {
            case 'admin_plataforma':
                return $this->repository->findAll();

            case 'admin_associacao_geral':
            case 'admin_horta_geral':
                return $this->repository->findAllDasHortas($this->hortasDoEscopo($payloadUsuarioLogado));

            case 'canteirista':
                return $this->repository->findAllDoUsuario($payloadUsuarioLogado['usuario_uuid']);

            default:
                throw new DomainException('Permissão insuficiente para consultar pagamentos');
        }
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

    public function delete(string $uuid, array $payloadUsuarioLogado)
    {
        $this->verificarCargoAdmin($payloadUsuarioLogado);
        $this->findByUuid($uuid, $payloadUsuarioLogado);

        $deleted = $this->repository->delete($uuid);
        if (!$deleted) {
            throw new InvalidArgumentException('Pagamento não encontrado');
        }
        return true;
    }

    private function verificarCargoAdmin(array $payloadUsuarioLogado): void
    {
        if (!in_array($this->getCargoSlug($payloadUsuarioLogado), self::CARGOS_ADMIN, true)) {
            throw new DomainException('Somente administradores podem lançar, editar ou excluir pagamentos');
        }
    }

    private function verificarCanteiristaNoEscopo($Canteirista, array $payloadUsuarioLogado): void
    {
        $cargo = $this->getCargoSlug($payloadUsuarioLogado);
        if ($cargo === 'admin_plataforma') return;

        if (!$Canteirista || !in_array($Canteirista->horta_uuid, $this->hortasDoEscopo($payloadUsuarioLogado), true)) {
            throw new DomainException('Este canteirista não é da sua associação ou horta');
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

    private function getCargoSlug(array $payloadUsuarioLogado): string
    {
        return $this->cargoService->findByUuidInternal($payloadUsuarioLogado['cargo_uuid'])->slug;
    }
}
