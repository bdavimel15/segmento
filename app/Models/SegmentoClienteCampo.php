<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SegmentoClienteCampo extends Model
{
    protected $table = 'segmento_cliente_campo';
    protected $primaryKey = 'segmento_cliente_campo_id';
    public $timestamps = false;

    protected $fillable = [
        'chave','label','descricao','categoria','tipo_valor','origem_tabela','origem_coluna',
        'expressao_sql','operadores_json','opcoes_json','ativo','ordem'
    ];

    protected $casts = [
        'operadores_json' => 'array',
        'opcoes_json' => 'array',
    ];
}
