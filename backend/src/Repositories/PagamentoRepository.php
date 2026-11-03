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

    // Escopo do admin_associacao_geral: pagamentos de mensalidades da associação
    // e pagamentos avulsos de canteiristas das hortas dela.
    public function findAllDaAssociacao(string $associacaoUuid, array $hortaUuids)
    {
        return $this->queryComRelacoes()
            ->where('excluido', 0)
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q
                    ->where('mensalidade_tipo', 'associacao')
                    ->whereHas('mensalidadeDaAssociacao', fn ($q) => $q->where('associacao_uuid', $associacaoUuid)))
                ->orWhereHas('Canteirista', fn ($q) => $q->whereIn('horta_uuid', $hortaUuids)))
            ->orderBy('data_pagamento', 'desc')
            ->get();
    }

    // Pagamentos de canteiristas das hortas informadas.
    public function findAllDasHortas(array $hortaUuids)
    {
        return $this->queryComRelacoes()
            ->where('excluido', 0)
            ->whereHas('Canteirista', fn ($q) => $q->whereIn('horta_uuid', $hortaUuids))
            ->orderBy('data_pagamento', 'desc')
            ->get();
    }

    // Pagamentos do canteirista ligado a este usuário.
    public function findAllDoUsuario(string $usuarioUuid)
    {
        return $this->queryComRelacoes()
            ->where('excluido', 0)
            ->whereHas('Canteirista', fn ($q) => $q->where('usuario_uuid', $usuarioUuid))
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

