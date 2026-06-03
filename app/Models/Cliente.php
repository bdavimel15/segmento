<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'cliente';
    protected $primaryKey = 'cliente_id';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'cli_nome',
        'cli_cpf',
        'sexo_id',
        'cli_telefone',
        'cli_email',
        'cli_data_nascimento',
        'cli_bairro',
        'cli_cidade',
        'cli_estado',
        'cli_qtd_pedidos',
        'cli_newsletter',
        'cli_funcionario',
        'cli_pontos_totais',
        'cli_proxima_compra',
        'excluido',
    ];

    protected $casts = [
        'cli_data_nascimento' => 'date',
        'cli_proxima_compra' => 'datetime',
        'excluido' => 'datetime',
    ];

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'cliente_id', 'cliente_id');
    }

    public function produtosComprados()
    {
        return $this->hasManyThrough(
            PedidoItem::class,
            Pedido::class,
            'cliente_id',
            'pedido_id',
            'cliente_id',
            'pedido_id'
        );
    }

    public function getSexoTextoAttribute(): string
    {
        $valor = mb_strtolower((string) $this->sexo_id);
        return match ($valor) {
            'm', '1', 'masculino', 'homem', 'homens' => 'Masculino',
            'f', '2', 'feminino', 'mulher', 'mulheres' => 'Feminino',
            default => $this->sexo_id ?: '-',
        };
    }
}
