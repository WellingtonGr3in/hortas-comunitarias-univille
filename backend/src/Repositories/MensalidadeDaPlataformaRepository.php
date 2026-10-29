<?php

namespace App\Repositories;

use App\Models\MensalidadeDaPlataformaModel;
use Illuminate\Database\Eloquent\Collection;

class MensalidadeDaPlataformaRepository
{
    protected MensalidadeDaPlataformaModel $mensalidadeDaPlataformaModel;

    public function __construct(MensalidadeDaPlataformaModel $mensalidadeDaPlataformaModel) {
        $this->mensalidadeDaPlataformaModel = $mensalidadeDaPlataformaModel;
    }

    public function findAllWhere(array $conditions): Collection
    {
        return $this->mensalidadeDaPlataformaModel->where($conditions)->get();
    }

    // SELECT ... FOR UPDATE: segura a linha até o fim da transação, para dois pagamentos
    // simultâneos da mesma mensalidade não passarem os dois pela checagem de duplicidade
    public function findByUuidParaAtualizar(string $uuid): ?MensalidadeDaPlataformaModel
    {
        return $this->mensalidadeDaPlataformaModel->newQuery()->where('uuid', $uuid)->lockForUpdate()->first();
    }

    public function findByUuid(string $uuid): ?MensalidadeDaPlataformaModel
    {
        return $this->mensalidadeDaPlataformaModel->find($uuid);
    }

    public function findPlanoByUsuarioUuid(string $usuarioUuid): ?string
    {
    $registro = $this->mensalidadeDaPlataformaModel
        ->where("usuario_uuid", $usuarioUuid)
        ->where("excluido", 0)
        ->orderBy("data_vencimento", "desc")
        ->first();

        return $registro?->plano_uuid;
    }

    public function findByUsuarioUuid(string $usuarioUuid): Collection
    {
        $registros = $this->mensalidadeDaPlataformaModel
        ->where("usuario_uuid", $usuarioUuid)
        ->where("excluido", 0)
        ->get();

        return $registros;
    }

    public function create(array $data): MensalidadeDaPlataformaModel
    {
        return $this->mensalidadeDaPlataformaModel->create($data);
    }

    public function update(MensalidadeDaPlataformaModel $mensalidadeDaPlataforma, array $data): ?MensalidadeDaPlataformaModel
    {
        $mensalidadeDaPlataforma->update($data);
        return $mensalidadeDaPlataforma;
    }

    public function delete(MensalidadeDaPlataformaModel $mensalidadeDaPlataforma, array $data): bool
    {
        $mensalidadeDaPlataforma->fill($data);
        return $mensalidadeDaPlataforma->save();
    }
}
