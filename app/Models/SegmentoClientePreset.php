<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SegmentoClientePreset extends Model
{
    protected $table = 'segmento_cliente_preset';
    protected $primaryKey = 'segmento_cliente_preset_id';
    public $timestamps = false;

    protected $fillable = ['nome','descricao','categoria','regra_json','ativo','ordem'];

    protected $casts = ['regra_json' => 'array'];
}
