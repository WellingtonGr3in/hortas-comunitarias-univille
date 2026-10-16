<?php

namespace App\Repositories;

use App\Models\ItemDoInventarioModel;
use Illuminate\Database\Eloquent\Collection;

class ItemDoInventarioRepository
{
    protected ItemDoInventarioModel $itemDoInventarioModel;

    public function __construct(ItemDoInventarioModel $itemDoInventarioModel) {
        $this->itemDoInventarioModel = $itemDoInventarioModel;
    }

    public function findAllWhere(array $conditions): Collection
    {
        return $this->itemDoInventarioModel->where($conditions)->orderBy('nome')->get();
    }

    // Itens da associação e das hortas vinculadas a ela.
    public function findAllDaAssociacaoEHortas(string $associacaoUuid): Collection
    {
        return $this->itemDoInventarioModel
            ->where('excluido', 0)
            ->where(function ($query) use ($associacaoUuid) {
                $query->where('associacao_uuid', $associacaoUuid)
                    ->orWhereIn('horta_uuid', function ($hortas) use ($associacaoUuid) {
                        $hortas->select('uuid')->from('hortas')->where('associacao_vinculada_uuid', $associacaoUuid);
                    });
            })
            ->orderBy('nome')
            ->get();
    }

    public function findByUuid(string $uuid): ?ItemDoInventarioModel
    {
        return $this->itemDoInventarioModel->find($uuid);
    }

    // Trava a linha até o fim da transação, para duas movimentações simultâneas não lerem o mesmo saldo.
    public function findByUuidParaAtualizar(string $uuid): ?ItemDoInventarioModel
    {
        return $this->itemDoInventarioModel->newQuery()->where('uuid', $uuid)->lockForUpdate()->first();
    }

    public function findByHortaUuid(string $hortaUuid): Collection
    {
        return $this->itemDoInventarioModel
        ->where('horta_uuid', $hortaUuid)
        ->where('excluido', 0)
        ->orderBy('nome')
        ->get();
    }

    public function findByAssociacaoUuid(string $associacaoUuid): Collection
    {
        return $this->itemDoInventarioModel
        ->where('associacao_uuid', $associacaoUuid)
        ->where('excluido', 0)
        ->orderBy('nome')
        ->get();
    }

    public function create(array $data): ItemDoInventarioModel
    {
        return $this->itemDoInventarioModel->create($data);
    }

    public function update(ItemDoInventarioModel $itemDoInventario, array $data): ?ItemDoInventarioModel
    {
        $itemDoInventario->update($data);
        return $itemDoInventario;
    }

    public function delete(ItemDoInventarioModel $itemDoInventario, array $data): bool
    {
        $itemDoInventario->fill($data);
        return $itemDoInventario->save();
    }
}
