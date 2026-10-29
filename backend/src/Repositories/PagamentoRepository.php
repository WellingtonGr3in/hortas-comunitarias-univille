<?php

namespace App\Repositories;

use App\Models\PagamentoModel;
use Ramsey\Uuid\Uuid;

class PagamentoRepository
{
    public function findAll()
    {
        return $this->queryComRelacoes()
            ->where('excluido', 0)
            ->orderBy('data_pagamento', 'desc')
            ->get();
    }

    // Pagamentos de mensalidades da associação informada (escopo do admin_associacao_geral)
    public function findAllDaAssociacao(string $associacaoUuid)
    {
        return $this->queryComRelacoes()
            ->where('excluido', 0)
            ->where('mensalidade_tipo', 'associacao')
            ->whereHas('mensalidadeDaAssociacao', fn ($q) => $q->where('associacao_uuid', $associacaoUuid))
            ->orderBy('data_pagamento', 'desc')
            ->get();
    }

    public function findAtivoPorMensalidade(string $tipo, string $mensalidadeUuid): ?PagamentoModel
    {
        return PagamentoModel::where('mensalidade_tipo', $tipo)
            ->where('mensalidade_uuid', $mensalidadeUuid)
            ->where('excluido', 0)
            ->first();
    }

    private function queryComRelacoes()
    {
        return PagamentoModel::with([
            'Canteirista',
            'mensalidadeDaAssociacao.usuario',
            'mensalidadeDaPlataforma.usuario',
        ]);
    }

    public function findByUuid(string $uuid)
    {
        return $this->queryComRelacoes()
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

