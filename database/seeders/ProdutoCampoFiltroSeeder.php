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

        $produtos = Schema::hasTable('produto')
            ? DB::table('produto')->whereNull('excluido')->orderBy('pro_nome')->pluck('pro_nome')->filter()->values()->all()
            : [];

        if ($produtos === []) {
            $produtos = ['Pizza Calabresa', 'Pizza Portuguesa', 'Pizza Frango com Catupiry', 'Picanha', 'Picanha Premium', 'Hambúrguer Artesanal', 'Açaí 500ml', 'Refrigerante Lata'];
        }

        $payload = [
            'chave' => 'produto_comprado',
            'label' => 'Produto comprado',
            'tipo_valor' => 'select',
            'descricao' => 'Filtra clientes que compraram um produto específico.',
            'categoria' => 'produto',
            'origem_tabela' => 'produto',
            'origem_coluna' => 'pro_nome',
            'expressao_sql' => 'prod.produtos_comprados',
            'operadores_json' => json_encode(['equals', 'not_equals', 'contains', 'not_contains', 'exists', 'not_exists'], JSON_UNESCAPED_UNICODE),
            'opcoes_json' => json_encode($produtos, JSON_UNESCAPED_UNICODE),
            'ativo' => 'S',
            'ordem' => 220,
        ];

        $row = [];
        foreach ($payload as $key => $value) {
            if (in_array($key, $columns, true)) {
                $row[$key] = $value;
            }
        }

        DB::table($table)->updateOrInsert(['chave' => 'produto_comprado'], $row);
    }
}
