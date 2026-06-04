<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SegmentoPresetSeeder extends Seeder
{
    public function run(): void
    {
        $presets = [
            [
                'nome' => 'Aniversariantes do dia',
                'descricao' => 'Clientes que fazem aniversário hoje',
                'categoria' => 'Aniversário',
                'regra_json' => json_encode(['version' => 1, 'entity' => 'cliente', 'logic' => 'AND', 'conditions' => [['field' => 'aniversario', 'operator' => 'today', 'value' => null]], 'limit' => 25, 'order' => ['field' => 'random', 'direction' => 'asc']], JSON_UNESCAPED_UNICODE),
                'ativo' => 'S',
                'ordem' => 10
            ],
            [
                'nome' => 'Clientes sem compra há 30 dias',
                'descricao' => 'Clientes com última compra há mais de 30 dias',
                'categoria' => 'Recência',
                'regra_json' => json_encode(['version' => 1, 'entity' => 'cliente', 'logic' => 'AND', 'conditions' => [['field' => 'ultima_compra', 'operator' => 'more_than_x_days_ago', 'value' => 30]], 'limit' => 25, 'order' => ['field' => 'random', 'direction' => 'asc']], JSON_UNESCAPED_UNICODE),
                'ativo' => 'S',
                'ordem' => 20
            ],
            [
                'nome' => 'Clientes com mais de 2 pedidos',
                'descricao' => 'Clientes com pelo menos 3 pedidos confirmados',
                'categoria' => 'Pedidos',
                'regra_json' => json_encode(['version' => 1, 'entity' => 'cliente', 'logic' => 'AND', 'conditions' => [['field' => 'qtd_pedidos_confirmados', 'operator' => 'greater_than', 'value' => 2]], 'limit' => 25, 'order' => ['field' => 'random', 'direction' => 'asc']], JSON_UNESCAPED_UNICODE),
                'ativo' => 'S',
                'ordem' => 30
            ],
            [
                'nome' => 'Clientes com cashback',
                'descricao' => 'Clientes com saldo de cashback maior que zero',
                'categoria' => 'Cashback',
                'regra_json' => json_encode(['version' => 1, 'entity' => 'cliente', 'logic' => 'AND', 'conditions' => [['field' => 'cashback_saldo', 'operator' => 'greater_than', 'value' => 0]], 'limit' => 25, 'order' => ['field' => 'random', 'direction' => 'asc']], JSON_UNESCAPED_UNICODE),
                'ativo' => 'S',
                'ordem' => 40
            ],
            [
                'nome' => 'Clientes cadastrados sem pedido',
                'descricao' => 'Clientes cadastrados e sem pedidos confirmados',
                'categoria' => 'Cadastro',
                'regra_json' => json_encode(['version' => 1, 'entity' => 'cliente', 'logic' => 'AND', 'conditions' => [['field' => 'qtd_pedidos_confirmados', 'operator' => 'equals', 'value' => 0]], 'limit' => 25, 'order' => ['field' => 'mais_recentes', 'direction' => 'desc']], JSON_UNESCAPED_UNICODE),
                'ativo' => 'S',
                'ordem' => 50
            ]
        ];

        foreach ($presets as $preset) {
            DB::table('segmento_cliente_preset')->updateOrInsert(
                ['nome' => $preset['nome']],
                $preset
            );
        }
    }
}
