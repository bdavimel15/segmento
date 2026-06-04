<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProdutoCampoFiltroSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('segmento_cliente_campo')) {
            return;
        }

        $table = 'segmento_cliente_campo';
        $columns = Schema::getColumnListing($table);

        $produtos = Schema::hasTable('produtos')
            ? DB::table('produtos')->where('ativo', 'S')->orderBy('nome')->pluck('nome')->values()->all()
            : [];

        $payload = [
            'campo' => 'produto_comprado',
            'nome' => 'Produto comprado',
            'tipo' => 'select',
            'descricao' => 'Filtra clientes que compraram um produto específico.',
            'ativo' => 'S',
            'opcoes' => json_encode($produtos, JSON_UNESCAPED_UNICODE),
            'categoria' => 'Pedido',
            'ordem' => 90,
            'cadastrado' => now(),
            'atualizado' => now(),
        ];

        $row = [];
        foreach ($payload as $key => $value) {
            if (in_array($key, $columns, true)) {
                $row[$key] = $value;
            }
        }

        if (in_array('campo', $columns, true)) {
            DB::table($table)->updateOrInsert(['campo' => 'produto_comprado'], $row);
        }
    }
}
