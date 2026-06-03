<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    protected $table = 'produto';
    protected $primaryKey = 'produto_id';

    const CREATED_AT = 'cadastrado';
    const UPDATED_AT = 'atualizado';

    protected $fillable = [
        'pro_nome',
        'pro_sku',
        'pro_preco',
        'excluido',
    ];

    protected $casts = [
        'pro_preco' => 'decimal:2',
        'excluido' => 'datetime',
        'cadastrado' => 'datetime',
        'atualizado' => 'datetime',
    ];

    public function itens()
    {
        return $this->hasMany(PedidoItem::class, 'produto_id', 'produto_id');
    }

    public function pedidos()
    {
        return $this->belongsToMany(
            Pedido::class,
            'pedido_item',
            'produto_id',
            'pedido_id',
            'produto_id',
            'pedido_id'
        )->withPivot(['pei_quantidade', 'pei_valor_unitario', 'pei_valor_total']);
    }
}
