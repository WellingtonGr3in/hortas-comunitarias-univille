<?php

namespace App\Repositories;

use App\Models\MovimentacaoDoInventarioModel;
use Illuminate\Database\Eloquent\Collection;

class MovimentacaoDoInventarioRepository
{
    protected MovimentacaoDoInventarioModel $movimentacaoDoInventarioModel;

    public function __construct(MovimentacaoDoInventarioModel $movimentacaoDoInventarioModel) {
        $this->movimentacaoDoInventarioModel = $movimentacaoDoInventarioModel;
    }

    public function findAllInItemUuidList(array $itemUuids): Collection
    {
        return $this->movimentacaoDoInventarioModel
            ->whereIn('item_uuid', $itemUuids)
            ->orderByDesc('data_de_criacao')
            ->get();
    }

    public function findByUuid(string $uuid): ?MovimentacaoDoInventarioModel
    {
        return $this->movimentacaoDoInventarioModel->find($uuid);
    }

    public function findByItemUuid(string $itemUuid): Collection
    {
        return $this->movimentacaoDoInventarioModel
            ->where('item_uuid', $itemUuid)
            ->orderByDesc('data_de_criacao')
            ->get();
    }

    public function create(array $data): MovimentacaoDoInventarioModel
    {
        return $this->movimentacaoDoInventarioModel->create($data);
    }
}
