<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemDoInventarioModel extends Model
{
    protected $table = 'itens_do_inventario';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = true;
    const CREATED_AT = 'data_de_criacao';
    const UPDATED_AT = 'data_de_ultima_alteracao';

    protected $fillable = [
        'uuid',
        'nome',
        'descricao',
        'tipo',
        'unidade_de_medida',
        'quantidade',
        'quantidade_minima',
        'associacao_uuid',
        'horta_uuid',
        'excluido',
        'usuario_criador_uuid',
        'usuario_alterador_uuid',
    ];

    protected $casts = [
        'quantidade' => 'float',
        'quantidade_minima' => 'float',
    ];

    // Relacionamentos
    public function associacao()
    {
        return $this->belongsTo(AssociacaoModel::class, 'associacao_uuid', 'uuid');
    }

    public function horta()
    {
        return $this->belongsTo(HortaModel::class, 'horta_uuid', 'uuid');
    }

    public function movimentacoes()
    {
        return $this->hasMany(MovimentacaoDoInventarioModel::class, 'item_uuid', 'uuid');
    }

    public function usuarioCriador()
    {
        return $this->belongsTo(UsuarioModel::class, 'usuario_criador_uuid', 'uuid');
    }

    public function usuarioAlterador()
    {
        return $this->belongsTo(UsuarioModel::class, 'usuario_alterador_uuid', 'uuid');
    }
}
