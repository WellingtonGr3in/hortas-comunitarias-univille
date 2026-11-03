<?php
namespace App\Services;

use App\Models\MovimentacaoDoInventarioModel;
use App\Repositories\ItemDoInventarioRepository;
use App\Repositories\MovimentacaoDoInventarioRepository;
use Respect\Validation\Validator as v;
use Exception;
use InvalidArgumentException;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\Uuid;

class MovimentacaoDoInventarioService
{
    public const TIPOS = ['entrada', 'saida', 'ajuste'];

    protected MovimentacaoDoInventarioRepository $movimentacaoDoInventarioRepository;
    protected ItemDoInventarioRepository $itemDoInventarioRepository;
    protected ItemDoInventarioService $itemDoInventarioService;
    protected Capsule $capsule;

    public function __construct(
        MovimentacaoDoInventarioRepository $movimentacaoDoInventarioRepository,
        ItemDoInventarioRepository $itemDoInventarioRepository,
        ItemDoInventarioService $itemDoInventarioService,
        Capsule $capsule
    ) {
        $this->movimentacaoDoInventarioRepository = $movimentacaoDoInventarioRepository;
        $this->itemDoInventarioRepository = $itemDoInventarioRepository;
        $this->itemDoInventarioService = $itemDoInventarioService;
        $this->capsule = $capsule;
    }

    // Movimentações de todos os itens que o usuário logado enxerga.
    public function findAllWhere(array $payloadUsuarioLogado): Collection
    {
        $itens = $this->itemDoInventarioService->findAllWhere($payloadUsuarioLogado);
        return $this->movimentacaoDoInventarioRepository->findAllInItemUuidList($itens->pluck('uuid')->all());
    }

    public function findByUuid(string $uuid, array $payloadUsuarioLogado): MovimentacaoDoInventarioModel
    {
        $movimentacao = $this->movimentacaoDoInventarioRepository->findByUuid($uuid);
        if (!$movimentacao) {
            throw new Exception('Movimentação do inventário não encontrada');
        }

        $this->itemDoInventarioService->findByUuid($movimentacao->item_uuid, $payloadUsuarioLogado);
        return $movimentacao;
    }

    public function findByItemUuid(string $itemUuid, array $payloadUsuarioLogado): Collection
    {
        $this->itemDoInventarioService->findByUuid($itemUuid, $payloadUsuarioLogado);
        return $this->movimentacaoDoInventarioRepository->findByItemUuid($itemUuid);
    }

    /**
     * entrada: soma a quantidade ao saldo.
     * saida: subtrai; é recusada se for maior que o saldo.
     * ajuste: a quantidade informada passa a ser o novo saldo (contagem física).
     */
    public function create(array $data, array $payloadUsuarioLogado): MovimentacaoDoInventarioModel
    {
        v::key('item_uuid', v::uuid())
            ->key('tipo', v::in(self::TIPOS))
            ->key('quantidade', v::numericVal()->min(0))
            ->key('observacao', v::optional(v::stringType()), false)
            ->assert($data);

        $tipo = $data['tipo'];
        $quantidade = round((float)$data['quantidade'], 3);

        if ($tipo !== 'ajuste' && $quantidade <= 0) {
            throw new InvalidArgumentException("A quantidade de uma {$tipo} deve ser maior que zero.");
        }

        return $this->capsule->connection()->transaction(function () use ($data, $tipo, $quantidade, $payloadUsuarioLogado) {
            $item = $this->itemDoInventarioRepository->findByUuidParaAtualizar($data['item_uuid']);
            if (!$item || $item->excluido) {
                throw new Exception('Item do inventário não encontrado');
            }
            $this->itemDoInventarioService->verificarAcesso($item, $payloadUsuarioLogado);

            $saldoAnterior = round((float)$item->quantidade, 3);

            switch ($tipo) {
                case 'entrada':
                    $saldoPosterior = $saldoAnterior + $quantidade;
                    break;

                case 'saida':
                    if ($quantidade > $saldoAnterior) {
                        throw new InvalidArgumentException(sprintf(
                            "Saída de %s %s maior que o estoque atual de %s (%s %s).",
                            $this->formatarQuantidade($quantidade), $item->unidade_de_medida,
                            $item->nome, $this->formatarQuantidade($saldoAnterior), $item->unidade_de_medida
                        ));
                    }
                    $saldoPosterior = $saldoAnterior - $quantidade;
                    break;

                default:
                    $saldoPosterior = $quantidade;
            }
            $saldoPosterior = round($saldoPosterior, 3);

            $movimentacao = $this->movimentacaoDoInventarioRepository->create([
                'uuid' => Uuid::uuid1()->toString(),
                'item_uuid' => $item->uuid,
                'tipo' => $tipo,
                'quantidade' => $quantidade,
                'quantidade_anterior' => $saldoAnterior,
                'quantidade_posterior' => $saldoPosterior,
                'observacao' => $data['observacao'] ?? null,
                'usuario_criador_uuid' => $payloadUsuarioLogado['usuario_uuid'],
            ]);

            $this->itemDoInventarioRepository->update($item, [
                'quantidade' => $saldoPosterior,
                'usuario_alterador_uuid' => $payloadUsuarioLogado['usuario_uuid'],
            ]);

            return $movimentacao;
        });
    }

    private function formatarQuantidade(float $quantidade): string
    {
        return rtrim(rtrim(number_format($quantidade, 3, ',', ''), '0'), ',');
    }
}
