<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoItem extends Model
{
    protected $table = 'pedido_item';
    protected $primaryKey = 'pedido_item_id';

    const CREATED_AT = 'cadastrado';
    const UPDATED_AT = 'atualizado';

    protected $fillable = [
        'pedido_id',
        'produto_id',
        'pei_quantidade',
        'pei_valor_unitario',
        'pei_valor_total',
        'excluido',
    ];

    protected $casts = [
        'pei_valor_unitario' => 'decimal:2',
        'pei_valor_total' => 'decimal:2',
        'excluido' => 'datetime',
        'cadastrado' => 'datetime',
        'atualizado' => 'datetime',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id', 'pedido_id');
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id', 'produto_id');
    }
}
