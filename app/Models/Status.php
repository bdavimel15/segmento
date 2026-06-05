<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    protected $table = 'status';
    protected $primaryKey = 'status_id';

    const CREATED_AT = 'cadastrado';
    const UPDATED_AT = 'atualizado';

    protected $fillable = [
        'sta_nome',
        'sta_confirmado',
        'sta_ativo',
        'excluido',
    ];

    protected $casts = [
        'excluido' => 'datetime',
        'cadastrado' => 'datetime',
        'atualizado' => 'datetime',
    ];

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'status_id', 'status_id');
    }
}
