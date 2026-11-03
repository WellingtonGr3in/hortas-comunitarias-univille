<?php
namespace App\Services;

use App\Exceptions\AcessoNegadoException;
use App\Models\ItemDoInventarioModel;
use App\Repositories\ItemDoInventarioRepository;
use App\Repositories\MovimentacaoDoInventarioRepository;
use Respect\Validation\Validator as v;
use Exception;
use InvalidArgumentException;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\Uuid;

class ItemDoInventarioService
{
    public const TIPOS = ['insumo', 'ferramenta'];

    protected ItemDoInventarioRepository $itemDoInventarioRepository;
    protected MovimentacaoDoInventarioRepository $movimentacaoDoInventarioRepository;
    protected AssociacaoService $associacaoService;
    protected HortaService $hortaService;
    protected CargoService $cargoService;
    protected Capsule $capsule;

    public function __construct(
        ItemDoInventarioRepository $itemDoInventarioRepository,
        MovimentacaoDoInventarioRepository $movimentacaoDoInventarioRepository,
        AssociacaoService $associacaoService,
        HortaService $hortaService,
        CargoService $cargoService,
        Capsule $capsule
    ) {
        $this->itemDoInventarioRepository = $itemDoInventarioRepository;
        $this->movimentacaoDoInventarioRepository = $movimentacaoDoInventarioRepository;
        $this->associacaoService = $associacaoService;
        $this->hortaService = $hortaService;
        $this->cargoService = $cargoService;
        $this->capsule = $capsule;
    }

    public function findAllWhere(array $payloadUsuarioLogado): Collection
    {
        $cargo = $this->getCargoSlug($payloadUsuarioLogado);

        switch ($cargo) {
            case 'admin_plataforma':
                return $this->itemDoInventarioRepository->findAllWhere(['excluido' => 0]);

            case 'admin_associacao_geral':
                return $this->itemDoInventarioRepository->findAllDaAssociacaoEHortas($payloadUsuarioLogado['associacao_uuid']);

            case 'admin_horta_geral':
                return $this->itemDoInventarioRepository->findAllWhere([
                    'excluido' => 0,
                    'horta_uuid' => $payloadUsuarioLogado['horta_uuid']
                ]);

            default:
                throw new AcessoNegadoException("Permissão insuficiente | findAllWhere");
        }
    }

    public function findByUuid(string $uuid, array $payloadUsuarioLogado): ItemDoInventarioModel
    {
        $item = $this->itemDoInventarioRepository->findByUuid($uuid);
        if (!$item || $item->excluido) {
            throw new Exception('Item do inventário não encontrado');
        }

        $this->verificarAcesso($item, $payloadUsuarioLogado);
        return $item;
    }

    public function findByHortaUuid(string $hortaUuid, array $payloadUsuarioLogado): Collection
    {
        // O HortaService já recusa hortas fora do escopo do cargo.
        $this->hortaService->findByUuid($hortaUuid, $payloadUsuarioLogado);
        $this->verificarCargoComAcessoAoInventario($payloadUsuarioLogado);

        return $this->itemDoInventarioRepository->findByHortaUuid($hortaUuid);
    }

    public function findByAssociacaoUuid(string $associacaoUuid, array $payloadUsuarioLogado): Collection
    {
        $cargo = $this->getCargoSlug($payloadUsuarioLogado);

        switch ($cargo) {
            case 'admin_plataforma':
                $this->associacaoService->findByUuid($associacaoUuid, $payloadUsuarioLogado);
                break;

            case 'admin_associacao_geral':
                if ($associacaoUuid !== $payloadUsuarioLogado['associacao_uuid']) {
                    throw new AcessoNegadoException("Você não tem permissão para acessar o inventário desta associação");
                }
                break;

            default:
                throw new AcessoNegadoException("Permissão insuficiente | findByAssociacaoUuid");
        }

        return $this->itemDoInventarioRepository->findByAssociacaoUuid($associacaoUuid);
    }

    public function create(array $data, array $payloadUsuarioLogado): ItemDoInventarioModel
    {
        v::key('nome', v::stringType()->notEmpty()->length(1, 100))
            ->key('descricao', v::optional(v::stringType()), false)
            ->key('tipo', v::in(self::TIPOS))
            ->key('unidade_de_medida', v::stringType()->notEmpty()->length(1, 20))
            ->key('quantidade', v::numericVal()->min(0), false)
            ->key('quantidade_minima', v::optional(v::numericVal()->min(0)), false)
            ->key('associacao_uuid', v::uuid(), false)
            ->key('horta_uuid', v::uuid(), false)
            ->assert($data);

        if (empty($data['associacao_uuid']) && empty($data['horta_uuid'])) {
            throw new InvalidArgumentException("É obrigatório informar associacao_uuid OU horta_uuid.");
        }
        if (!empty($data['associacao_uuid']) && !empty($data['horta_uuid'])) {
            throw new InvalidArgumentException("Não é permitido informar associacao_uuid E horta_uuid ao mesmo tempo.");
        }

        $this->verificarDono($data['associacao_uuid'] ?? null, $data['horta_uuid'] ?? null, $payloadUsuarioLogado);

        $quantidadeInicial = round((float)($data['quantidade'] ?? 0), 3);

        $dadosDoItem = [
            'uuid' => Uuid::uuid1()->toString(),
            'nome' => $data['nome'],
            'descricao' => $data['descricao'] ?? null,
            'tipo' => $data['tipo'],
            'unidade_de_medida' => $data['unidade_de_medida'],
            'quantidade' => $quantidadeInicial,
            'quantidade_minima' => $data['quantidade_minima'] ?? null,
            'associacao_uuid' => $data['associacao_uuid'] ?? null,
            'horta_uuid' => $data['horta_uuid'] ?? null,
            'usuario_criador_uuid' => $payloadUsuarioLogado['usuario_uuid'],
            'usuario_alterador_uuid' => $payloadUsuarioLogado['usuario_uuid'],
        ];

        // O saldo inicial vira uma movimentação de entrada, para o histórico bater com a quantidade.
        return $this->capsule->connection()->transaction(function () use ($dadosDoItem, $quantidadeInicial, $payloadUsuarioLogado) {
            $item = $this->itemDoInventarioRepository->create($dadosDoItem);

            if ($quantidadeInicial > 0) {
                $this->movimentacaoDoInventarioRepository->create([
                    'uuid' => Uuid::uuid1()->toString(),
                    'item_uuid' => $item->uuid,
                    'tipo' => 'entrada',
                    'quantidade' => $quantidadeInicial,
                    'quantidade_anterior' => 0,
                    'quantidade_posterior' => $quantidadeInicial,
                    'observacao' => 'Saldo inicial',
                    'usuario_criador_uuid' => $payloadUsuarioLogado['usuario_uuid'],
                ]);
            }

            return $item;
        });
    }

    public function update(string $uuid, array $data, array $payloadUsuarioLogado): ItemDoInventarioModel
    {
        $item = $this->findByUuid($uuid, $payloadUsuarioLogado);

        if (array_key_exists('quantidade', $data)) {
            throw new InvalidArgumentException("A quantidade não é editada diretamente: registre uma movimentação (entrada, saída ou ajuste).");
        }
        if (array_key_exists('associacao_uuid', $data) || array_key_exists('horta_uuid', $data)) {
            throw new InvalidArgumentException("Não é permitido mudar a associação ou a horta de um item.");
        }

        v::key('nome', v::stringType()->notEmpty()->length(1, 100), false)
            ->key('descricao', v::optional(v::stringType()), false)
            ->key('tipo', v::in(self::TIPOS), false)
            ->key('unidade_de_medida', v::stringType()->notEmpty()->length(1, 20), false)
            ->key('quantidade_minima', v::optional(v::numericVal()->min(0)), false)
            ->assert($data);

        $editaveis = ['nome', 'descricao', 'tipo', 'unidade_de_medida', 'quantidade_minima'];
        $data = array_intersect_key($data, array_flip($editaveis));
        $data['usuario_alterador_uuid'] = $payloadUsuarioLogado['usuario_uuid'];

        return $this->itemDoInventarioRepository->update($item, $data);
    }

    public function delete(string $uuid, array $payloadUsuarioLogado): bool
    {
        $item = $this->findByUuid($uuid, $payloadUsuarioLogado);

        $data = [
            'excluido' => 1,
            'usuario_alterador_uuid' => $payloadUsuarioLogado['usuario_uuid'],
        ];

        return $this->itemDoInventarioRepository->delete($item, $data);
    }

    // Quem pode ver um item também pode gerenciá-lo e movimentá-lo.
    public function verificarAcesso(ItemDoInventarioModel $item, array $payloadUsuarioLogado): void
    {
        $this->verificarDono($item->associacao_uuid, $item->horta_uuid, $payloadUsuarioLogado);
    }

    private function verificarDono(?string $associacaoUuid, ?string $hortaUuid, array $payloadUsuarioLogado): void
    {
        $cargo = $this->getCargoSlug($payloadUsuarioLogado);

        switch ($cargo) {
            case 'admin_plataforma':
                if ($associacaoUuid) $this->associacaoService->findByUuid($associacaoUuid, $payloadUsuarioLogado);
                if ($hortaUuid) $this->hortaService->findByUuid($hortaUuid, $payloadUsuarioLogado);
                return;

            case 'admin_associacao_geral':
                // A própria associação e as hortas vinculadas a ela.
                if ($associacaoUuid && $associacaoUuid === $payloadUsuarioLogado['associacao_uuid']) return;
                if ($hortaUuid) {
                    $horta = $this->hortaService->findByUuidForValidation($hortaUuid);
                    if ($horta && !$horta->excluido && $horta->associacao_vinculada_uuid === $payloadUsuarioLogado['associacao_uuid']) return;
                }
                throw new AcessoNegadoException("Você não tem permissão para acessar este inventário");

            case 'admin_horta_geral':
                if ($hortaUuid && $hortaUuid === $payloadUsuarioLogado['horta_uuid']) return;
                throw new AcessoNegadoException("Você não tem permissão para acessar este inventário");

            default:
                throw new AcessoNegadoException("Permissão insuficiente | inventário");
        }
    }

    private function verificarCargoComAcessoAoInventario(array $payloadUsuarioLogado): void
    {
        $cargo = $this->getCargoSlug($payloadUsuarioLogado);
        if (!in_array($cargo, ['admin_plataforma', 'admin_associacao_geral', 'admin_horta_geral'], true)) {
            throw new AcessoNegadoException("Permissão insuficiente | inventário");
        }
    }

    private function getCargoSlug(array $payloadUsuarioLogado): string
    {
        return $this->cargoService->findByUuidInternal($payloadUsuarioLogado['cargo_uuid'])->slug;
    }
}
