<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SegmentoCliente extends Model
{
    protected $table = 'segmento_cliente';
    protected $primaryKey = 'segmento_cliente_id';
    public $timestamps = false;

    protected $fillable = [
        'nome','descricao','tipo','origem','regra_json','resumo_humano','status_validacao',
        'limite','ordenacao','ultima_previa_qtd','ultima_previa_em','validado_em','validado_por','motivo_reprovacao','excluido'
    ];

    protected $casts = [
        'regra_json' => 'array',
        'ultima_previa_em' => 'datetime',
        'excluido' => 'datetime',
    ];
}
