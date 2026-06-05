<?php

namespace Database\Seeders;

use App\Models\SegmentoClienteCampo;
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

        $produtos = [];
        if (Schema::hasTable('produtos')) {
            $query = DB::table('produtos')->whereNotNull('nome')->where('nome', '!=', '');
            if (Schema::hasColumn('produtos', 'ativo')) {
                $query->where('ativo', 'S');
            }
            $produtos = $query->orderBy('nome')->pluck('nome')->filter()->values()->all();
        }

        if ($produtos === []) {
            return;
        }

        SegmentoClienteCampo::updateOrCreate(
            ['chave' => 'produto_comprado'],
            [
                'label' => 'Produto comprado',
                'descricao' => 'Produto comprado em algum pedido confirmado.',
                'categoria' => 'produto',
                'tipo_valor' => 'select',
                'expressao_sql' => 'prod.produtos_comprados',
                'operadores_json' => ['equals', 'not_equals', 'contains', 'not_contains', 'starts_with', 'ends_with', 'is_empty', 'is_not_empty'],
                'opcoes_json' => $produtos,
                'ativo' => 'S',
                'ordem' => 20,
            ]
        );
    }
}
