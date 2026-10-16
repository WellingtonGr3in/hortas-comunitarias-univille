<?php

namespace App\Repositories;

use App\Models\PagamentoModel;
use Ramsey\Uuid\Uuid;

class PagamentoRepository
{
    public function findAll()
    {
        return PagamentoModel::with('Canteirista')
            ->where('excluido', 0)
            ->orderBy('data_pagamento', 'desc')
            ->get();
    }

    // Pagamentos de canteiristas das hortas informadas.
    public function findAllDasHortas(array $hortaUuids)
    {
        return PagamentoModel::with('Canteirista')
            ->where('excluido', 0)
            ->whereHas('Canteirista', fn ($q) => $q->whereIn('horta_uuid', $hortaUuids))
            ->orderBy('data_pagamento', 'desc')
            ->get();
    }

    // Pagamentos do canteirista ligado a este usuário.
    public function findAllDoUsuario(string $usuarioUuid)
    {
        return PagamentoModel::with('Canteirista')
            ->where('excluido', 0)
            ->whereHas('Canteirista', fn ($q) => $q->where('usuario_uuid', $usuarioUuid))
            ->orderBy('data_pagamento', 'desc')
            ->get();
    }

    public function findByUuid(string $uuid)
    {
        return PagamentoModel::with('Canteirista')
            ->where('uuid', $uuid)
            ->where('excluido', 0)
            ->first();
    }

    public function create(array $data)
    {
        $data['uuid'] = Uuid::uuid4()->toString();
        return PagamentoModel::create($data);
    }

    public function update(string $uuid, array $data)
    {
        $pagamento = $this->findByUuid($uuid);
        if (!$pagamento) {
            return null;
        }
        $pagamento->update($data);
        return $pagamento;
    }

    public function delete(string $uuid)
    {
        $pagamento = $this->findByUuid($uuid);
        if (!$pagamento) {
            return false;
        }
        $pagamento->excluido = 1;
        $pagamento->save();
        return true;
    }
}

