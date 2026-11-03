<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimentacaoDoInventarioModel extends Model
{
    protected $table = 'movimentacoes_do_inventario';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    // Movimentações não são alteradas depois de registradas.
    public $timestamps = true;
    const CREATED_AT = 'data_de_criacao';
    const UPDATED_AT = null;

    protected $fillable = [
        'uuid',
        'item_uuid',
        'tipo',
        'quantidade',
        'quantidade_anterior',
        'quantidade_posterior',
        'observacao',
        'usuario_criador_uuid',
    ];

    protected $casts = [
        'quantidade' => 'float',
        'quantidade_anterior' => 'float',
        'quantidade_posterior' => 'float',
    ];

    // Relacionamentos
    public function item()
    {
        return $this->belongsTo(ItemDoInventarioModel::class, 'item_uuid', 'uuid');
    }

    public function usuarioCriador()
    {
        return $this->belongsTo(UsuarioModel::class, 'usuario_criador_uuid', 'uuid');
    }
}
