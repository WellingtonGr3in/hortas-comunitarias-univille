<?php

namespace App\Services;

use App\Exceptions\AcessoNegadoException;
use App\Models\CanteiristaModel;
use App\Repositories\CanteiroRepository;
use App\Repositories\CanteiristaRepository;
use App\Repositories\CargoRepository;
use App\Repositories\HortaRepository;
use Exception;
use Ramsey\Uuid\Nonstandard\Uuid;

/**
 * Quem pode o quê:
 * - admin_plataforma: todos os canteiristas.
 * - admin_associacao_geral: canteiristas das hortas da sua associação.
 * - admin_horta_geral: canteiristas da sua horta.
 * - canteirista: só consulta o próprio cadastro.
 * Cadastrar, editar, excluir, ativar e desativar é só para os cargos de administração.
 */
class CanteiristaService
{
    private const CARGOS_ADMIN = ['admin_plataforma', 'admin_associacao_geral', 'admin_horta_geral'];

    protected CanteiristaRepository $CanteiristaRepository;
    protected CanteiroRepository $canteiroRepository;
    protected HortaService $HortaService;
    protected CargoRepository $cargoRepository;
    protected UsuarioService $usuarioService;
    protected HortaRepository $hortaRepository;
    protected CargoService $cargoService;

    public function __construct(CanteiristaRepository $CanteiristaRepository,
        CanteiroRepository $canteiroRepository,
        HortaService $HortaService,
        CargoRepository $cargoRepository,
        UsuarioService $usuarioService,
        HortaRepository $hortaRepository,
        CargoService $cargoService
    )
    {
        $this->hortaRepository = $hortaRepository;
        $this->cargoService = $cargoService;
        $this->CanteiristaRepository = $CanteiristaRepository;
        $this->canteiroRepository = $canteiroRepository;
        $this->HortaService = $HortaService;
        $this->cargoRepository = $cargoRepository;
        $this->usuarioService = $usuarioService;
    }

    public function findAllWhere(array $payloadUsuarioLogado)
    {
        $Canteiristas = $this->CanteiristaRepository->findAll();

        if ($this->getCargoSlug($payloadUsuarioLogado) === 'canteirista') {
            return $Canteiristas->filter(fn($c) => $c->usuario_uuid === $payloadUsuarioLogado['usuario_uuid'])->values();
        }

        $hortas = $this->hortasDoEscopo($payloadUsuarioLogado);
        if ($hortas === null) {
            return $Canteiristas;
        }
        return $Canteiristas->filter(fn($c) => in_array($c->horta_uuid, $hortas, true))->values();
    }

    public function findByUuid(string $uuid, array $payloadUsuarioLogado)
    {
        $Canteirista = $this->CanteiristaRepository->findByUuid($uuid);
        if (!$Canteirista) {
            return null;
        }

        if ($this->getCargoSlug($payloadUsuarioLogado) === 'canteirista') {
            if ($Canteirista->usuario_uuid !== $payloadUsuarioLogado['usuario_uuid']) {
                throw new AcessoNegadoException('Você só pode ver o seu próprio cadastro');
            }
            return $Canteirista;
        }

        $this->verificarHortaNoEscopo($Canteirista->horta_uuid, $payloadUsuarioLogado);
        return $Canteirista;
    }

    public function create(array $data, array $payloadUsuarioLogado): CanteiristaModel
    {
        return \Illuminate\Database\Capsule\Manager::connection()->transaction(
            fn() => $this->createWithUsuario($data, $payloadUsuarioLogado)
        );
    }

    private function createWithUsuario(array $data, array $payloadUsuarioLogado): CanteiristaModel
    {
        $this->verificarCargoAdmin($payloadUsuarioLogado);

        $canteiros = $data['canteiros'] ?? [];
        unset($data['canteiros']);

        if (empty($data['cpf'])) {
            throw new Exception("Cpf é obrigatório");
        }
        if (empty($data['nome_completo'])) {
            throw new Exception("Nome completo é obrigatório");
        }
        if (empty($data['telefone'])) {
            throw new Exception("Telefone é obrigatório");
        }
        if (empty($data['horta_uuid'])) {
            throw new Exception("Horta é obrigatória");
        }

        $usuarioData = [
            'nome_completo' => $data['nome_completo'] ?? null,
            'cpf' => $data['cpf'] ?? null,
            'email' => $data['email'] ?? null,
            'senha' => $data['senha'] ?? null,
            'data_de_nascimento' => $data['data_de_nascimento'] ?? null,
            'apelido' => $data['apelido'] ?? null,
            'endereco_uuid' => $data['endereco_uuid'] ?? null,
        ];

        foreach (['cpf', 'nome_completo', 'email', 'senha', 'data_de_nascimento', 'apelido', 'endereco_uuid'] as $field) {
            unset($data[$field]);
        }

        $guarded = ['uuid','usuario_criador_uuid','data_de_criacao','data_de_ultima_alteracao', 'usuario_uuid'];
        foreach ($guarded as $g) unset($data[$g]);

        // Validações básicas
        $horta = $this->HortaService->findByUuid($data['horta_uuid'], $payloadUsuarioLogado);

        if (!$horta) {
            throw new Exception("Horta não encontrada");
        }
        $this->verificarHortaNoEscopo($horta->uuid, $payloadUsuarioLogado);

        // O usuário precisa saber a horta e a associação: elas vão no token e definem o que ele enxerga.
        $usuarioData['horta_uuid'] = $horta->uuid;
        $usuarioData['associacao_uuid'] = $horta->associacao_vinculada_uuid;

        // Validar dados do usuário
        if (empty($usuarioData['email'])) {
            throw new Exception("Email é obrigatório para criar o usuário");
        }
        if (empty($usuarioData['senha'])) {
            throw new Exception("Senha é obrigatória para criar o usuário");
        }
        if (empty($usuarioData['data_de_nascimento'])) {
            throw new Exception("Data de nascimento é obrigatória para criar o usuário");
        }
        if (empty($usuarioData['apelido'])) {
            throw new Exception("Apelido é obrigatório para criar o usuário");
        }

        $cargo = $this->cargoRepository->findAllWhere(['slug' => 'canteirista'])->first();
        if ($cargo) {
            $usuarioData['cargo_uuid'] = $cargo->uuid;
        }

        $usuario = $this->usuarioService->create($usuarioData, "NEW_ACCOUNT", $payloadUsuarioLogado);

        $data['uuid'] = Uuid::uuid1()->toString();
        $data['usuario_criador_uuid'] =  $payloadUsuarioLogado['usuario_uuid'];
        $data['usuario_alterador_uuid'] =  $payloadUsuarioLogado['usuario_uuid'];
        $data['usuario_uuid'] = $usuario->uuid;
        $data['ativo'] = 1;
        $data['excluido'] = 0;

        $Canteirista = $this->CanteiristaRepository->create($data);

        if (!empty($canteiros)) {
            $this->syncCanteiros($Canteirista, $canteiros, $payloadUsuarioLogado['usuario_uuid']);
            $Canteirista->refresh();
        }

        return $Canteirista;
    }

    public function update(string $uuid, array $data, array $payloadUsuarioLogado)
    {
        $this->verificarCargoAdmin($payloadUsuarioLogado);
        if (!$this->findByUuid($uuid, $payloadUsuarioLogado)) {
            return null;
        }
        if (!empty($data['horta_uuid'])) {
            $this->verificarHortaNoEscopo($data['horta_uuid'], $payloadUsuarioLogado);
        }

        $canteiros = $data['canteiros'] ?? [];
        unset($data['canteiros']);

        $usuarioData = [];
        foreach (['nome_completo', 'cpf', 'email', 'senha', 'data_de_nascimento', 'apelido', 'endereco_uuid'] as $field) {
            if (array_key_exists($field, $data)) {
                $usuarioData[$field] = $data[$field];
                unset($data[$field]);
            }
        }

        // Adiciona o UUID do usuário alterador
        $data['usuario_alterador_uuid'] = $payloadUsuarioLogado['usuario_uuid'];

        // Remove campos que não devem ser atualizados
        unset($data['uuid']);
        unset($data['usuario_criador_uuid']);
        unset($data['usuario_uuid']);
        unset($data['data_de_criacao']);

        $Canteirista = $this->CanteiristaRepository->findByUuid($uuid);
        if (!$Canteirista) {
            return null;
        }

        if (!empty($usuarioData) && !empty($Canteirista->usuario_uuid)) {
            $this->usuarioService->update(
                $Canteirista->usuario_uuid,
                $usuarioData,
                $payloadUsuarioLogado['usuario_uuid'],
                $payloadUsuarioLogado
            );
        }

        $Canteirista = $this->CanteiristaRepository->update($uuid, $data);

        if ($Canteirista && !empty($canteiros)) {
            $this->syncCanteiros($Canteirista, $canteiros, $payloadUsuarioLogado['usuario_uuid']);
            $Canteirista->refresh();
        }

        return $Canteirista;
    }

    private function syncCanteiros(CanteiristaModel $Canteirista, array $canteiros, string $usuarioUuid)
    {
        $syncData = [];

        // agora $canteiros é um array simples de UUIDs de canteiro
        foreach ($canteiros as $canteiroUuid) {
            $cant = $this->canteiroRepository->findByUuid($canteiroUuid);

            if (!$cant) {
                throw new Exception("Canteiro não encontrado: $canteiroUuid");
            }

            if (empty($canteiroUuid)) {
                throw new Exception('UUID do canteiro é obrigatório em cada entrada de canteiros.');
            }

            if ($cant['horta_uuid'] !== $Canteirista->horta_uuid) {
                throw new Exception('O canteiro a ser adicionado deve pertencer à mesma horta do Canteirista.');
            }

            $syncData[$canteiroUuid] = [
                'uuid' => Uuid::uuid1()->toString(),
                'ativo' => 1,
                'excluido' => 0,
                'usuario_criador_uuid' => $usuarioUuid,
                'usuario_alterador_uuid' => $usuarioUuid,
            ];
        }

        $Canteirista->canteiros()->sync($syncData);
    }

    public function delete(string $uuid, array $payloadUsuarioLogado)
    {
        $this->verificarCargoAdmin($payloadUsuarioLogado);
        $Canteirista = $this->findByUuid($uuid, $payloadUsuarioLogado);

        if (!$Canteirista) {
            throw new Exception("Canteirista não encontrado");
        }

        return $this->CanteiristaRepository->delete($uuid);
    }

    /**
     * Ativa o acesso do canteirista (libera o login do usuário vinculado).
     *
     * @param string $uuid UUID do canteirista
     * @param array $payloadUsuarioLogado
     * @return CanteiristaModel|null
     */
    public function ativar(string $uuid, array $payloadUsuarioLogado): ?CanteiristaModel
    {
        return $this->setStatusAcesso(
            $uuid,
            'ativo',
            null,
            null,
            $payloadUsuarioLogado
        );
    }

    /**
     * Desativa o acesso do canteirista (bloqueia o login do usuário vinculado).
     *
     * @param string $uuid UUID do canteirista
     * @param array $payloadUsuarioLogado
     * @param string|null $motivo Motivo opcional do bloqueio
     * @return CanteiristaModel|null
     */
    public function desativar(string $uuid, array $payloadUsuarioLogado, ?string $motivo = null): ?CanteiristaModel
    {
        return $this->setStatusAcesso(
            $uuid,
            'inativo',
            date('Y-m-d H:i:s'),
            $motivo,
            $payloadUsuarioLogado
        );
    }

    private function setStatusAcesso(
        string $uuid,
        string $status,
        ?string $dataBloqueio,
        ?string $motivo,
        array $payloadUsuarioLogado
    ): ?CanteiristaModel {
        $this->verificarCargoAdmin($payloadUsuarioLogado);
        $canteirista = $this->findByUuid($uuid, $payloadUsuarioLogado);

        if (!$canteirista) {
            return null;
        }

        if (empty($canteirista->usuario_uuid)) {
            throw new Exception('Canteirista não possui usuário vinculado para alterar acesso');
        }

        $this->usuarioService->update(
            $canteirista->usuario_uuid,
            [
                'status_de_acesso' => $status,
                'data_bloqueio_acesso' => $dataBloqueio,
                'motivo_bloqueio_acesso' => $motivo,
            ],
            $payloadUsuarioLogado['usuario_uuid'],
            $payloadUsuarioLogado
        );

        // Atualiza o usuario_alterador_uuid do canteirista para refletir a ação.
        $this->CanteiristaRepository->update($uuid, [
            'usuario_alterador_uuid' => $payloadUsuarioLogado['usuario_uuid'],
        ]);

        return $this->CanteiristaRepository->findByUuid($uuid);
    }

    /**
     * Lista canteiristas aplicando os filtros recebidos via query string.
     *
     * @param array $filtros
     * @param array $payloadUsuarioLogado
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByFilters(array $filtros, array $payloadUsuarioLogado)
    {
        $this->verificarCargoAdmin($payloadUsuarioLogado);

        // Restringe a busca às hortas do escopo de quem consulta.
        $hortas = $this->hortasDoEscopo($payloadUsuarioLogado);
        if ($hortas !== null) {
            $filtros['hortas_uuid'] = $hortas;
        }

        return $this->CanteiristaRepository->findByFilters($filtros);
    }

    /**
     * Retorna estatísticas agregadas sobre os canteiristas.
     *
     * @param array $payloadUsuarioLogado
     * @param array $opcoes  meses (int) — janela para a série temporal mensal.
     * @return array
     */
    public function getEstatisticas(array $payloadUsuarioLogado, array $opcoes = []): array
    {
        $this->verificarCargoAdmin($payloadUsuarioLogado);

        $meses = isset($opcoes['meses']) ? (int) $opcoes['meses'] : 6;
        if ($meses < 1) {
            $meses = 6;
        }

        // Restringe as estatísticas às hortas do escopo (null = todas).
        $hortaUuid = $this->hortasDoEscopo($payloadUsuarioLogado);

        $total = $this->CanteiristaRepository->countTotal($hortaUuid);
        $comCanteiros = $this->CanteiristaRepository->countComCanteirosVinculados($hortaUuid);
        $semCanteiros = $total - $comCanteiros;
        $totalCanteirosVinculados = $this->CanteiristaRepository->countCanteirosVinculados($hortaUuid);

        $mediaCanteirosPorCanteirista = $total > 0
            ? round($totalCanteirosVinculados / $total, 2)
            : 0;

        return [
            'total' => $total,
            'com_canteiros_vinculados' => $comCanteiros,
            'sem_canteiros_vinculados' => $semCanteiros,
            'total_canteiros_vinculados' => $totalCanteirosVinculados,
            'media_canteiros_por_Canteirista' => $mediaCanteirosPorCanteirista,
            'por_horta' => $this->CanteiristaRepository->countPorHorta($hortaUuid),
            'por_mes' => $this->CanteiristaRepository->countPorMes($meses, $hortaUuid),
        ];
    }

    private function verificarCargoAdmin(array $payloadUsuarioLogado): void
    {
        if (!in_array($this->getCargoSlug($payloadUsuarioLogado), self::CARGOS_ADMIN, true)) {
            throw new AcessoNegadoException('Somente administradores podem gerenciar canteiristas');
        }
    }

    private function verificarHortaNoEscopo(?string $hortaUuid, array $payloadUsuarioLogado): void
    {
        $hortas = $this->hortasDoEscopo($payloadUsuarioLogado);
        if ($hortas !== null && !in_array($hortaUuid, $hortas, true)) {
            throw new AcessoNegadoException('Este canteirista não é da sua associação ou horta');
        }
    }

    // Hortas que o usuário enxerga: null para o admin da plataforma (todas).
    private function hortasDoEscopo(array $payloadUsuarioLogado): ?array
    {
        $cargo = $this->getCargoSlug($payloadUsuarioLogado);

        if ($cargo === 'admin_plataforma') {
            return null;
        }
        if ($cargo === 'admin_associacao_geral') {
            return $this->hortaRepository->findAllWhere([
                'associacao_vinculada_uuid' => $payloadUsuarioLogado['associacao_uuid'],
                'excluido' => 0,
            ])->pluck('uuid')->all();
        }
        return array_values(array_filter([$payloadUsuarioLogado['horta_uuid'] ?? null]));
    }

    private function getCargoSlug(array $payloadUsuarioLogado): string
    {
        return $this->cargoService->findByUuidInternal($payloadUsuarioLogado['cargo_uuid'])->slug;
    }
}
