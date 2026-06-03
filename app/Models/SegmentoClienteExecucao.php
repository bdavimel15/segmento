<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SegmentoClienteExecucao extends Model
{
    protected $table = 'segmento_cliente_execucao';
    protected $primaryKey = 'segmento_cliente_execucao_id';
    public $timestamps = false;

    protected $fillable = [
        'segmento_cliente_id','canal','regra_json_snapshot','sql_gerada_snapshot','total_encontrado',
        'total_processado','total_enviado','status','erro','executado_por','executado_em'
    ];

    protected $casts = [
        'regra_json_snapshot' => 'array',
    ];
}
