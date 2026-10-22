<?php

namespace App\Services;

use App\Exceptions\AcessoNegadoException;
use App\Repositories\CanteiristaRepository;
use App\Repositories\DependenteRepository;
use App\Repositories\HortaRepository;

/**
 * Quem pode o quê:
 * - admin_plataforma: todos os dependentes.
 * - admin_associacao_geral: dependentes dos canteiristas das hortas da sua associação.
 * - admin_horta_geral: dependentes dos canteiristas da sua horta.
 * - canteirista: só consulta os próprios dependentes.
 * Cadastrar, editar e excluir é só para os cargos de administração.
 */
class DependenteService
{
    private const CARGOS_ADMIN = ['admin_plataforma', 'admin_associacao_geral', 'admin_horta_geral'];

    protected DependenteRepository $dependenteRepository;
    protected CanteiristaRepository $CanteiristaRepository;
    protected HortaRepository $hortaRepository;
    protected CargoService $cargoService;

    public function __construct(
        DependenteRepository $dependenteRepository,
        CanteiristaRepository $CanteiristaRepository,
        HortaRepository $hortaRepository,
        CargoService $cargoService
    ) {
        $this->dependenteRepository = $dependenteRepository;
        $this->CanteiristaRepository = $CanteiristaRepository;
        $this->hortaRepository = $hortaRepository;
        $this->cargoService = $cargoService;
    }

    public function findAllWhere(array $payloadUsuarioLogado)
    {
        $cargo = $this->getCargoSlug($payloadUsuarioLogado);
        $dependentes = $this->dependenteRepository->findAll();

        switch ($cargo) {
            case 'admin_plataforma':
                return $dependentes;

            case 'admin_associacao_geral':
            case 'admin_horta_geral':
                $hortas = $this->hortasDoEscopo($payloadUsuarioLogado);
                return $dependentes->filter(fn($d) => in_array($d->Canteirista?->horta_uuid, $hortas, true))->values();

            case 'canteirista':
                return $dependentes->filter(fn($d) => $d->Canteirista?->usuario_uuid === $payloadUsuarioLogado['usuario_uuid'])->values();

            default:
                throw new AcessoNegadoException('Permissão insuficiente para consultar dependentes');
        }
    }

    public function findByUuid(string $uuid, array $payloadUsuarioLogado)
    {
        $dependente = $this->dependenteRepository->findByUuid($uuid);
        if (!$dependente) {
            return null;
        }

        $cargo = $this->getCargoSlug($payloadUsuarioLogado);
        if ($cargo === 'canteirista') {
            if ($dependente->Canteirista?->usuario_uuid !== $payloadUsuarioLogado['usuario_uuid']) {
                throw new AcessoNegadoException('Você só pode ver os seus dependentes');
            }
            return $dependente;
        }

        $this->verificarCanteiristaNoEscopo($dependente->Canteirista, $payloadUsuarioLogado);
        return $dependente;
    }

    public function create(array $data, array $payloadUsuarioLogado)
    {
        $this->verificarCargoAdmin($payloadUsuarioLogado);

        if (empty($data['carteirista_uuid'])) {
            throw new \InvalidArgumentException('Canteirista (responsável) é obrigatório');
        }
        $this->verificarCanteiristaNoEscopo($this->CanteiristaRepository->findByUuid($data['carteirista_uuid']), $payloadUsuarioLogado);

        // Adiciona o UUID do usuário criador
        $data['usuario_criador_uuid'] = $payloadUsuarioLogado['usuario_uuid'];

        // Se não informou ativo, assume true
        if (!isset($data['ativo'])) {
            $data['ativo'] = true;
        }

        return $this->dependenteRepository->create($data);
    }

    public function update(string $uuid, array $data, array $payloadUsuarioLogado)
    {
        $this->verificarCargoAdmin($payloadUsuarioLogado);
        if (!$this->findByUuid($uuid, $payloadUsuarioLogado)) {
            return null;
        }
        if (!empty($data['carteirista_uuid'])) {
            $this->verificarCanteiristaNoEscopo($this->CanteiristaRepository->findByUuid($data['carteirista_uuid']), $payloadUsuarioLogado);
        }

        // Adiciona o UUID do usuário alterador
        $data['usuario_alterador_uuid'] = $payloadUsuarioLogado['usuario_uuid'];

        // Remove campos que não devem ser atualizados
        unset($data['uuid']);
        unset($data['usuario_criador_uuid']);
        unset($data['data_de_criacao']);

        return $this->dependenteRepository->update($uuid, $data);
    }

    public function delete(string $uuid, array $payloadUsuarioLogado)
    {
        $this->verificarCargoAdmin($payloadUsuarioLogado);
        if (!$this->findByUuid($uuid, $payloadUsuarioLogado)) {
            return false;
        }
        return $this->dependenteRepository->delete($uuid);
    }

    private function verificarCargoAdmin(array $payloadUsuarioLogado): void
    {
        if (!in_array($this->getCargoSlug($payloadUsuarioLogado), self::CARGOS_ADMIN, true)) {
            throw new AcessoNegadoException('Somente administradores podem cadastrar, editar ou excluir dependentes');
        }
    }

    private function verificarCanteiristaNoEscopo($Canteirista, array $payloadUsuarioLogado): void
    {
        if ($this->getCargoSlug($payloadUsuarioLogado) === 'admin_plataforma') {
            if (!$Canteirista) {
                throw new \InvalidArgumentException('Canteirista não encontrado');
            }
            return;
        }

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

    private function getCargoSlug(array $payloadUsuarioLogado): string
    {
        return $this->cargoService->findByUuidInternal($payloadUsuarioLogado['cargo_uuid'])->slug;
    }
}
