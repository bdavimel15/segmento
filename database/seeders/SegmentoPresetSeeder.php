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
            $this->preset('Clientes que compraram hoje', 'Compras', 'Clientes com último pedido confirmado no dia atual.', 'ultimo_pedido today', 25, 'random asc', '🛍️', '#7C5CFF'),
            $this->preset('Clientes que compraram ontem', 'Compras', 'Clientes com último pedido confirmado ontem.', 'ultimo_pedido yesterday', 25, 'random asc', '🛒', '#7C5CFF'),
            $this->preset('Clientes sem comprar há 30 dias', 'Recência', 'Clientes que não compram há mais de 30 dias.', 'ultimo_pedido more_than_x_days_ago 30', 25, 'random asc', '⏳', '#F59E0B'),
            $this->preset('Clientes sem comprar há 60 dias', 'Recência', 'Clientes que não compram há mais de 60 dias.', 'ultimo_pedido more_than_x_days_ago 60', 25, 'random asc', '⏳', '#F97316'),
            $this->preset('Clientes sem comprar há 90 dias', 'Recência', 'Clientes que não compram há mais de 90 dias.', 'ultimo_pedido more_than_x_days_ago 90', 25, 'random asc', '⚠️', '#EF4444'),
            $this->preset('Clientes com cashback', 'Cashback', 'Clientes com saldo de cashback maior que zero.', 'cashback greater_than 0', 25, 'random asc', '💰', '#22C55E'),
            $this->preset('Cashback expirando em 7 dias', 'Cashback', 'Clientes com cashback próximo do vencimento.', 'cashback_expira_em next_x_days 7', 25, 'random asc', '⏰', '#F59E0B'),
            $this->preset('Cashback expirando em 30 dias', 'Cashback', 'Clientes com cashback vencendo nos próximos 30 dias.', 'cashback_expira_em next_x_days 30', 25, 'random asc', '💸', '#F59E0B'),
            $this->preset('Aniversariantes do dia', 'Aniversário', 'Clientes que fazem aniversário hoje.', 'nascimento today', 25, 'random asc', '🎂', '#EC4899'),
            $this->preset('Aniversariantes do mês', 'Aniversário', 'Clientes que fazem aniversário no mês atual.', 'nascimento month_equals current', 25, 'random asc', '🎁', '#EC4899'),
            $this->preset('Clientes cadastrados nos últimos 7 dias', 'Cadastro', 'Clientes novos cadastrados nos últimos 7 dias.', 'data_cadastro last_x_days 7', 25, 'data_cadastro desc', '🆕', '#3B82F6'),
            $this->preset('Clientes cadastrados nos últimos 30 dias', 'Cadastro', 'Clientes novos cadastrados nos últimos 30 dias.', 'data_cadastro last_x_days 30', 25, 'data_cadastro desc', '📋', '#3B82F6'),
            $this->preset('Clientes cadastrados sem pedido', 'Cadastro', 'Clientes cadastrados que ainda não fizeram pedidos confirmados.', 'qtd_pedidos equals 0', 25, 'random asc', '🧾', '#6366F1'),
            $this->preset('Clientes com carrinho abandonado', 'Carrinho', 'Clientes com carrinho abandonado para recuperação.', 'carrinho_abandonado is_true', 25, 'random asc', '🛒', '#F97316'),
            $this->preset('Clientes com newsletter ativa', 'Engajamento', 'Clientes que aceitaram receber comunicações.', 'newsletter is_true', 25, 'random asc', '📩', '#14B8A6'),
            $this->preset('Clientes sem newsletter', 'Engajamento', 'Clientes que não aceitaram newsletter.', 'newsletter is_false', 25, 'random asc', '🚫', '#64748B'),
            $this->preset('Clientes com mais de 2 pedidos', 'Pedidos', 'Clientes com pelo menos 3 pedidos confirmados.', 'qtd_pedidos greater_than 2', 25, 'qtd_pedidos desc', '📦', '#8B5CF6'),
            $this->preset('Clientes com mais de 5 pedidos', 'Pedidos', 'Clientes recorrentes com mais de 5 pedidos confirmados.', 'qtd_pedidos greater_than 5', 25, 'qtd_pedidos desc', '⭐', '#8B5CF6'),
            $this->preset('Top 10 clientes por pedidos', 'Pedidos', 'Ranking dos 10 clientes com mais pedidos.', 'qtd_pedidos greater_than 0', 10, 'qtd_pedidos desc', '🏆', '#EAB308'),
            $this->preset('Top 10 clientes por valor comprado', 'Compras', 'Ranking dos clientes com maior valor total comprado.', 'valor_total_comprado greater_than 0', 10, 'valor_total_comprado desc', '💎', '#EAB308'),
            $this->preset('Clientes mulheres', 'Perfil', 'Clientes com sexo feminino.', 'sexo equals Feminino', 25, 'random asc', '👩', '#EC4899'),
            $this->preset('Clientes homens', 'Perfil', 'Clientes com sexo masculino.', 'sexo equals Masculino', 25, 'random asc', '👨', '#3B82F6'),
            $this->preset('Clientes de Feira de Santana', 'Localização', 'Clientes cadastrados no município de Feira de Santana.', 'municipio contains Feira de Santana', 25, 'random asc', '📍', '#10B981'),
            $this->preset('Clientes com pontos', 'Fidelidade', 'Clientes com pontos acumulados no programa de fidelidade.', 'pontos_totais greater_than 0', 25, 'pontos_totais desc', '🎯', '#8B5CF6'),
            $this->preset('Clientes que compraram picanha', 'Produtos', 'Clientes que compraram o produto Picanha.', 'produto_comprado contains picanha', 25, 'random asc', '🥩', '#EF4444'),
            $this->preset('Clientes que compraram bebida', 'Produtos', 'Clientes que compraram produtos relacionados a bebidas.', 'produto_comprado contains bebida', 25, 'random asc', '🥤', '#06B6D4'),
            $this->preset('Clientes homens sem comprar há 30 dias', 'Combinados', 'Homens que não compram há mais de 30 dias.', 'sexo equals Masculino AND ultimo_pedido more_than_x_days_ago 30', 25, 'random asc', '🔄', '#7C5CFF'),
            $this->preset('Mulheres com cashback', 'Combinados', 'Clientes mulheres que possuem cashback disponível.', 'sexo equals Feminino AND cashback greater_than 0', 25, 'random asc', '💜', '#A855F7'),
        ];

        $now = now();

        DB::table($table)->delete();

        foreach ($presets as $index => $preset) {
            DB::table($table)->insert($this->buildRow($table, $preset, $index + 1, $now));
        }
    }

    private function preset(string $nome, string $categoria, string $descricao, string $conditions, int $limit, string $order, string $icone, string $cor): array
    {
        return [
            'nome' => $nome,
            'categoria' => $categoria,
            'descricao' => $descricao,
            'icone' => $icone,
            'cor' => $cor,
            'regra_json' => [
                'version' => 1,
                'entity' => 'cliente',
                'logic' => 'AND',
                'conditions' => $conditions,
                'limit' => $limit,
                'order' => $order,
            ],
        ];
    }

    private function buildRow(string $table, array $preset, int $ordem, $now): array
    {
        $columns = Schema::getColumnListing($table);
        $regraJson = json_encode($preset['regra_json'], JSON_UNESCAPED_UNICODE);

        $candidates = [
            'nome' => $preset['nome'],
            'titulo' => $preset['nome'],
            'categoria' => $preset['categoria'],
            'descricao' => $preset['descricao'],
            'resumo' => $preset['descricao'],
            'icone' => $preset['icone'],
            'cor' => $preset['cor'],
            'regra_json' => $regraJson,
            'json' => $regraJson,
            'payload' => $regraJson,
            'ativo' => 'S',
            'status' => 'ativo',
            'ordem' => $ordem,
            'criado_em' => $now,
            'atualizado_em' => $now,
            'created_at' => $now,
            'updated_at' => $now,
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
