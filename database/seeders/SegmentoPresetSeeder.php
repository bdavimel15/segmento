<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SegmentoPresetSeeder extends Seeder
{
    public function run(): void
    {
        $table = 'segmento_cliente_preset';

        if (! Schema::hasTable($table)) {
            return;
        }

        $presets = [
            $this->make('Clientes que compraram hoje', 'Compras', 'Clientes com último pedido confirmado no dia atual.', [['field' => 'ultimo_pedido', 'operator' => 'today', 'value' => null]]),
            $this->make('Clientes que compraram ontem', 'Compras', 'Clientes com último pedido confirmado ontem.', [['field' => 'ultimo_pedido', 'operator' => 'yesterday', 'value' => null]]),
            $this->make('Clientes sem comprar há 30 dias', 'Recência', 'Clientes que não compram há mais de 30 dias.', [['field' => 'ultimo_pedido', 'operator' => 'more_than_x_days_ago', 'value' => 30]]),
            $this->make('Clientes sem comprar há 60 dias', 'Recência', 'Clientes que não compram há mais de 60 dias.', [['field' => 'ultimo_pedido', 'operator' => 'more_than_x_days_ago', 'value' => 60]]),
            $this->make('Clientes sem comprar há 90 dias', 'Recência', 'Clientes que não compram há mais de 90 dias.', [['field' => 'ultimo_pedido', 'operator' => 'more_than_x_days_ago', 'value' => 90]]),
            $this->make('Clientes com cashback', 'Cashback', 'Clientes com saldo de cashback maior que zero.', [['field' => 'cashback', 'operator' => 'greater_than', 'value' => 0]]),
            $this->make('Cashback expirando em 7 dias', 'Cashback', 'Clientes com cashback próximo do vencimento.', [['field' => 'cashback_expira_em', 'operator' => 'next_x_days', 'value' => 7]]),
            $this->make('Cashback expirando em 30 dias', 'Cashback', 'Clientes com cashback vencendo nos próximos 30 dias.', [['field' => 'cashback_expira_em', 'operator' => 'next_x_days', 'value' => 30]]),
            $this->make('Aniversariantes do dia', 'Aniversário', 'Clientes que fazem aniversário hoje.', [['field' => 'nascimento', 'operator' => 'today', 'value' => null]]),
            $this->make('Aniversariantes do mês', 'Aniversário', 'Clientes que fazem aniversário no mês atual.', [['field' => 'nascimento', 'operator' => 'month_equals', 'value' => 'current']]),
            $this->make('Clientes cadastrados nos últimos 7 dias', 'Cadastro', 'Clientes novos cadastrados nos últimos 7 dias.', [['field' => 'data_cadastro', 'operator' => 'last_x_days', 'value' => 7]], 25, 'data_cadastro', 'desc'),
            $this->make('Clientes cadastrados nos últimos 30 dias', 'Cadastro', 'Clientes novos cadastrados nos últimos 30 dias.', [['field' => 'data_cadastro', 'operator' => 'last_x_days', 'value' => 30]], 25, 'data_cadastro', 'desc'),
            $this->make('Clientes cadastrados sem pedido', 'Cadastro', 'Clientes cadastrados que ainda não fizeram pedidos confirmados.', [['field' => 'qtd_pedidos', 'operator' => 'equals', 'value' => 0]]),
            $this->make('Clientes com carrinho abandonado', 'Carrinho', 'Clientes com carrinho abandonado para recuperação.', [['field' => 'carrinho_abandonado', 'operator' => 'is_true', 'value' => null]]),
            $this->make('Clientes com newsletter ativa', 'Engajamento', 'Clientes que aceitaram receber comunicações.', [['field' => 'newsletter', 'operator' => 'is_true', 'value' => null]]),
            $this->make('Clientes sem newsletter', 'Engajamento', 'Clientes que não aceitaram newsletter.', [['field' => 'newsletter', 'operator' => 'is_false', 'value' => null]]),
            $this->make('Clientes com mais de 2 pedidos', 'Pedidos', 'Clientes com pelo menos 3 pedidos confirmados.', [['field' => 'qtd_pedidos', 'operator' => 'greater_than', 'value' => 2]], 25, 'qtd_pedidos', 'desc'),
            $this->make('Clientes com mais de 5 pedidos', 'Pedidos', 'Clientes recorrentes com mais de 5 pedidos confirmados.', [['field' => 'qtd_pedidos', 'operator' => 'greater_than', 'value' => 5]], 25, 'qtd_pedidos', 'desc'),
            $this->make('Top 10 clientes por pedidos', 'Pedidos', 'Ranking dos 10 clientes com mais pedidos.', [['field' => 'qtd_pedidos', 'operator' => 'greater_than', 'value' => 0]], 10, 'qtd_pedidos', 'desc'),
            $this->make('Top 10 clientes por valor comprado', 'Compras', 'Ranking dos clientes com maior valor total comprado.', [['field' => 'valor_total_comprado', 'operator' => 'greater_than', 'value' => 0]], 10, 'valor_total_comprado', 'desc'),
            $this->make('Clientes mulheres', 'Perfil', 'Clientes com sexo feminino.', [['field' => 'sexo', 'operator' => 'equals', 'value' => 'Feminino']]),
            $this->make('Clientes homens', 'Perfil', 'Clientes com sexo masculino.', [['field' => 'sexo', 'operator' => 'equals', 'value' => 'Masculino']]),
            $this->make('Clientes de Feira de Santana', 'Localização', 'Clientes cadastrados no município de Feira de Santana.', [['field' => 'municipio', 'operator' => 'contains', 'value' => 'Feira de Santana']]),
            $this->make('Clientes com pontos', 'Fidelidade', 'Clientes com pontos acumulados no programa de fidelidade.', [['field' => 'pontos_totais', 'operator' => 'greater_than', 'value' => 0]], 25, 'pontos_totais', 'desc'),
            $this->make('Clientes que compraram picanha', 'Produtos', 'Clientes que compraram o produto Picanha.', [['field' => 'produto_comprado', 'operator' => 'contains', 'value' => 'picanha']]),
            $this->make('Clientes que compraram bebida', 'Produtos', 'Clientes que compraram produtos relacionados a bebidas.', [['field' => 'produto_comprado', 'operator' => 'contains', 'value' => 'bebida']]),
            $this->make('Clientes homens sem comprar há 30 dias', 'Combinados', 'Homens que não compram há mais de 30 dias.', [
                ['field' => 'sexo', 'operator' => 'equals', 'value' => 'Masculino'],
                ['field' => 'ultimo_pedido', 'operator' => 'more_than_x_days_ago', 'value' => 30],
            ]),
            $this->make('Mulheres com cashback', 'Combinados', 'Clientes mulheres que possuem cashback disponível.', [
                ['field' => 'sexo', 'operator' => 'equals', 'value' => 'Feminino'],
                ['field' => 'cashback', 'operator' => 'greater_than', 'value' => 0],
            ]),
        ];

        $now = now();

        DB::table($table)->delete();

        foreach ($presets as $index => $preset) {
            DB::table($table)->insert($this->buildRow($table, $preset, $index + 1, $now));
        }
    }

    /**
     * @param  array<int, array{field: string, operator: string, value: mixed}>  $conditions
     * @return array<string, mixed>
     */
    private function make(
        string $nome,
        string $categoria,
        string $descricao,
        array $conditions,
        int $limit = 25,
        string $orderField = 'random',
        string $orderDirection = 'asc',
    ): array {
        return [
            'nome' => $nome,
            'categoria' => $categoria,
            'descricao' => $descricao,
            'regra_json' => [
                'version' => 2,
                'entity' => 'cliente',
                'logic' => 'AND',
                'groups' => [[
                    'logic' => 'AND',
                    'conditions' => $conditions,
                ]],
                'conditions' => [],
                'limit' => $limit,
                'order' => [
                    'field' => $orderField,
                    'direction' => $orderDirection,
                ],
                'resumo_humano' => $descricao,
            ],
        ];
    }

    /** @param  array<string, mixed>  $preset */
    private function buildRow(string $table, array $preset, int $ordem, $now): array
    {
        $columns = Schema::getColumnListing($table);
        $regraJson = json_encode($preset['regra_json'], JSON_UNESCAPED_UNICODE);

        $candidates = [
            'nome' => $preset['nome'],
            'categoria' => $preset['categoria'],
            'descricao' => $preset['descricao'],
            'regra_json' => $regraJson,
            'ativo' => 'S',
            'ordem' => $ordem,
            'cadastrado' => $now,
            'atualizado' => $now,
        ];

        $row = [];

        foreach ($candidates as $column => $value) {
            if (in_array($column, $columns, true)) {
                $row[$column] = $value;
            }
        }

        return $row;
    }
}
