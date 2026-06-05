<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedido';
    protected $primaryKey = 'pedido_id';

    const CREATED_AT = 'cadastrado';
    const UPDATED_AT = 'atualizado';

    protected $fillable = [
        'ped_numero',
        'ped_data',
        'cliente_id',
        'estabelecimento_id',
        'status_id',
        'ped_valor_total',
        'excluido',
    ];

    protected $casts = [
        'ped_data' => 'datetime',
        'ped_valor_total' => 'decimal:2',
        'excluido' => 'datetime',
        'cadastrado' => 'datetime',
        'atualizado' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'cliente_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id', 'status_id');
    }

    public function itens()
    {
        return $this->hasMany(PedidoItem::class, 'pedido_id', 'pedido_id');
    }

    public function produtos()
    {
        return $this->belongsToMany(
            Produto::class,
            'pedido_item',
            'pedido_id',
            'produto_id',
            'pedido_id',
            'produto_id'
        )->withPivot(['pei_quantidade', 'pei_valor_unitario', 'pei_valor_total']);
    }
}
