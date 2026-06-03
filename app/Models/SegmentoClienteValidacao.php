<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SegmentoClienteValidacao extends Model
{
    protected $table = 'segmento_cliente_validacao';
    protected $primaryKey = 'segmento_cliente_validacao_id';
    public $timestamps = false;

    protected $fillable = [
        'segmento_cliente_id',
        'status_anterior',
        'status_novo',
        'regra_json_snapshot',
        'resumo_humano_snapshot',
        'observacao',
        'validado_por',
        'cadastrado',
    ];

    protected $casts = [
        'regra_json_snapshot' => 'array',
    ];
}
