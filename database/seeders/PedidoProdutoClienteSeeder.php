<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PedidoProdutoClienteSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('pedido_produto_cliente') || ! Schema::hasTable('produtos')) {
            return;
        }

        $clienteTable = Schema::hasTable('cliente') ? 'cliente' : (Schema::hasTable('customers') ? 'customers' : null);
        $pedidoTable = Schema::hasTable('pedido') ? 'pedido' : (Schema::hasTable('orders') ? 'orders' : null);

        if (! $clienteTable || ! $pedidoTable) {
            return;
        }

        $clienteIdColumn = $clienteTable === 'cliente' ? 'cliente_id' : 'id';
        $pedidoIdColumn = $pedidoTable === 'pedido' ? 'pedido_id' : 'id';

        $clientes = DB::table($clienteTable)->pluck($clienteIdColumn)->values();
        $pedidos = DB::table($pedidoTable)->pluck($pedidoIdColumn)->values();
        $produtos = DB::table('produtos')->select('produto_id', 'preco')->get()->values();

        if ($clientes->isEmpty() || $pedidos->isEmpty() || $produtos->isEmpty()) {
            return;
        }

        DB::table('pedido_produto_cliente')->delete();

        $now = now();
        $rows = [];

        foreach ($pedidos as $index => $pedidoId) {
            $clienteId = $clientes[$index % $clientes->count()];
            $qtdProdutosNoPedido = rand(1, 3);

            for ($i = 0; $i < $qtdProdutosNoPedido; $i++) {
                $produto = $produtos[($index + $i) % $produtos->count()];
                $quantidade = rand(1, 3);
                $valorUnitario = (float) $produto->preco;

                $rows[] = [
                    'cliente_id' => $clienteId,
                    'pedido_id' => $pedidoId,
                    'produto_id' => $produto->produto_id,
                    'quantidade' => $quantidade,
                    'valor_unitario' => $valorUnitario,
                    'valor_total' => $valorUnitario * $quantidade,
                    'cadastrado' => $now,
                    'atualizado' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('pedido_produto_cliente')->insert($chunk);
        }
    }
}
