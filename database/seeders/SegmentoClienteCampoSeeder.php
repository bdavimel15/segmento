<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SegmentoClienteCampoSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('segmento_cliente_campo')) {
            return;
        }

        $opsTexto = ['equals', 'not_equals', 'contains', 'not_contains', 'starts_with', 'ends_with', 'is_empty', 'is_not_empty'];
        $opsNumero = ['equals', 'not_equals', 'greater_than', 'greater_or_equal', 'less_than', 'less_or_equal', 'between'];
        $opsData = ['today', 'yesterday', 'equals_date', 'before_date', 'after_date', 'between_dates', 'last_x_days', 'next_x_days', 'exactly_x_days_ago', 'more_than_x_days_ago', 'less_than_x_days_ago', 'month_equals', 'month_between', 'is_empty', 'is_not_empty'];
        $opsBool = ['equals', 'not_equals', 'is_true', 'is_false'];
        $opsSelect = ['equals', 'not_equals', 'is_empty', 'is_not_empty'];
        $opsProduto = ['equals', 'not_equals', 'contains', 'not_contains', 'exists', 'not_exists'];

        $campos = [
            $this->campo('nome', 'Nome do cliente', 'Nome cadastrado do cliente', 'cliente', 'string', 'cliente', 'cli_nome', 'c.cli_nome', $opsTexto, 10),
            $this->campo('cpf', 'CPF do cliente', 'CPF cadastrado do cliente', 'cliente', 'string', 'cliente', 'cli_cpf', 'c.cli_cpf', $opsTexto, 20),
            $this->campo('sexo', 'Sexo', 'Sexo cadastrado do cliente', 'cliente', 'select', 'cliente', 'sexo_id', 'c.sexo_id', $opsSelect, 30, ['Masculino', 'Feminino']),
            $this->campo('telefone', 'Telefone do cliente', 'Telefone/celular do cliente', 'cliente', 'string', 'cliente', 'cli_telefone', 'c.cli_telefone', $opsTexto, 40),
            $this->campo('email', 'E-mail do cliente', 'E-mail cadastrado do cliente', 'cliente', 'string', 'cliente', 'cli_email', 'c.cli_email', $opsTexto, 50),
            $this->campo('nascimento', 'Data de nascimento', 'Data de nascimento do cliente', 'cliente', 'date', 'cliente', 'cli_data_nascimento', 'c.cli_data_nascimento', $opsData, 60),
            $this->campo('aniversario', 'Aniversário', 'Aniversário calculado pelo mês e dia do nascimento', 'cliente', 'date', 'cliente', 'cli_data_nascimento', 'c.cli_data_nascimento', ['today', 'equals_date', 'next_x_days', 'between_dates', 'month_equals', 'month_between'], 61),
            $this->campo('idade', 'Idade', 'Idade calculada pela data de nascimento', 'cliente', 'number', 'cliente', 'cli_data_nascimento', $this->idadeSql(), $opsNumero, 70),

            $this->campo('bairro', 'Bairro', 'Bairro cadastrado do cliente', 'endereco', 'string', 'cliente', 'cli_bairro', 'c.cli_bairro', $opsTexto, 80),
            $this->campo('municipio', 'Cidade', 'Cidade/município cadastrada do cliente', 'endereco', 'string', 'cliente', 'cli_cidade', 'c.cli_cidade', $opsTexto, 90),
            $this->campo('estado', 'Estado (UF)', 'Estado/UF cadastrado do cliente', 'endereco', 'select', 'cliente', 'cli_estado', 'c.cli_estado', $opsSelect, 100, ['BA', 'SP', 'RJ', 'MG', 'PE', 'CE', 'PR', 'SC', 'RS', 'GO', 'DF', 'ES']),

            $this->campo('funcionario', 'Funcionário(a)', 'Indica se o cliente é funcionário da empresa', 'cliente', 'select', 'cliente', 'cli_funcionario', 'c.cli_funcionario', $opsBool, 110, ['Sim', 'Não']),
            $this->campo('newsletter', 'Newsletter', 'Indica se o cliente aceita receber comunicações', 'cliente', 'select', 'cliente', 'cli_newsletter', 'c.cli_newsletter', $opsBool, 120, ['Sim', 'Não']),

            $this->campo('data_cadastro', 'Data de cadastro', 'Data em que o cliente foi cadastrado', 'cliente', 'datetime', 'cliente', 'cadastrado', 'c.cadastrado', $opsData, 130),

            $this->campo('qtd_pedidos', 'Quantidade de pedidos', 'Quantidade total de pedidos confirmados', 'pedido', 'number', 'pedido', 'pedido_id', 'COALESCE(ps.qtd_pedidos, 0)', $opsNumero, 140),
            $this->campo('ultima_compra', 'Última compra', 'Data da última compra confirmada', 'pedido', 'datetime', 'pedido', 'ped_data', 'ps.ultimo_pedido', $opsData, 150),
            $this->campo('primeira_compra', 'Primeira compra', 'Data da primeira compra confirmada', 'pedido', 'datetime', 'pedido', 'ped_data', 'ps.primeira_compra', $opsData, 160),
            $this->campo('valor_total_comprado', 'Valor total comprado', 'Soma dos valores de pedidos confirmados', 'pedido', 'money', 'pedido', 'ped_valor_total', 'COALESCE(ps.valor_total_comprado, 0)', $opsNumero, 170),

            $this->campo('status_pedido', 'Status do pedido', 'Status do pedido confirmado, pendente ou cancelado', 'pedido', 'select', 'status', 'sta_nome', 'pcanal.status_pedido', $opsSelect, 175, ['Confirmado', 'Cancelado', 'Pendente']),
            $this->campo('canal_pedido', 'Canal do pedido', 'Canal por onde o pedido foi realizado', 'pedido', 'select', 'pedido', 'canal_pedido', 'pcanal.canal_pedido', $opsSelect, 176, ['WhatsApp', 'Balcão', 'Delivery próprio', 'iFood', 'Site', 'Instagram']),
            $this->campo('forma_pagamento', 'Forma de pagamento', 'Forma de pagamento usada no pedido', 'pedido', 'select', 'pedido', 'forma_pagamento', 'pcanal.forma_pagamento', $opsSelect, 177, ['Pix', 'Cartão de crédito', 'Cartão de débito', 'Dinheiro', 'Vale refeição']),

            $this->campo('cashback', 'Saldo de cashback', 'Saldo total de cashback disponível do cliente', 'cashback', 'money', 'cashback', 'cas_valor', 'COALESCE(cb.cashback, 0)', $opsNumero, 180),
            $this->campo('cashback_expira_em', 'Cashback expira em', 'Data estimada de expiração do cashback', 'cashback', 'datetime', 'cashback', 'cadastrado', 'cb.cashback_expira_em', $opsData, 190),
            $this->campo('pontos_totais', 'Pontos totais', 'Pontos acumulados do cliente', 'cliente', 'number', 'cliente', 'cli_pontos_totais', 'COALESCE(c.cli_pontos_totais, 0)', $opsNumero, 200),

            $this->campo('carrinho_abandonado', 'Carrinho abandonado', 'Indica se o cliente possui carrinho abandonado', 'carrinho', 'boolean', 'cliente', 'cliente_id', 'vca.cliente_id', ['is_true', 'is_false', 'exists', 'not_exists'], 210),
            $this->campo('produto_comprado', 'Produto comprado', 'Produto comprado pelo cliente', 'produto', 'select', 'produto', 'pro_nome', 'prod.produtos_comprados', $opsProduto, 220, $this->produtosOptions()),

            $this->campo('origem_contato', 'Origem do contato', 'Origem do contato/cliente', 'contato', 'select', 'cliente', 'cliente_origem_id', "CASE CAST(c.cliente_origem_id AS INTEGER) WHEN 1 THEN 'WhatsApp' WHEN 2 THEN 'Instagram' WHEN 3 THEN 'Balcão' WHEN 4 THEN 'iFood' WHEN 5 THEN 'Site' ELSE CAST(c.cliente_origem_id AS CHAR) END", $opsSelect, 230, ['WhatsApp', 'Instagram', 'Balcão', 'iFood', 'Site', 'Indicação']),
            $this->campo('recebeu_notificacao_nos_ultimos_dias', 'Recebeu notificação recentemente', 'Verifica se o cliente recebeu notificação recentemente', 'notificacao', 'number', 'notificacao_programada_envio', 'cadastrado', 'npe.cadastrado', ['exists', 'not_exists', 'last_x_days', 'less_than_x_days_ago'], 240),
            $this->campo('busca_geral', 'Busca geral', 'Busca em nome, telefone, e-mail e CPF', 'sistema', 'string', 'cliente', null, "COALESCE(c.cli_nome, '') || ' ' || COALESCE(c.cli_telefone, '') || ' ' || COALESCE(c.cli_email, '') || ' ' || COALESCE(c.cli_cpf, '')", ['contains', 'not_contains', 'starts_with', 'ends_with'], 250),
        ];

        DB::table('segmento_cliente_campo')->delete();

        foreach ($campos as $campo) {
            DB::table('segmento_cliente_campo')->updateOrInsert(
                ['chave' => $campo['chave']],
                $this->onlyExistingColumns('segmento_cliente_campo', $campo)
            );
        }
    }

    private function campo(
        string $chave,
        string $label,
        string $descricao,
        string $categoria,
        string $tipoValor,
        ?string $origemTabela,
        ?string $origemColuna,
        ?string $expressaoSql,
        array $operadores,
        int $ordem,
        array $opcoes = []
    ): array {
        return [
            'chave' => $chave,
            'label' => $label,
            'descricao' => $descricao,
            'categoria' => $categoria,
            'tipo_valor' => $tipoValor,
            'origem_tabela' => $origemTabela,
            'origem_coluna' => $origemColuna,
            'expressao_sql' => $expressaoSql,
            'operadores_json' => json_encode($operadores, JSON_UNESCAPED_UNICODE),
            'opcoes_json' => $opcoes !== [] ? json_encode(array_values(array_unique($opcoes)), JSON_UNESCAPED_UNICODE) : null,
            'ativo' => 'S',
            'ordem' => $ordem,
        ];
    }

    private function produtosOptions(): array
    {
        if (Schema::hasTable('produto')) {
            $produtos = DB::table('produto')
                ->whereNull('excluido')
                ->orderBy('pro_nome')
                ->pluck('pro_nome')
                ->filter()
                ->values()
                ->all();

            if ($produtos !== []) {
                return $produtos;
            }
        }

        return ['Pizza Calabresa', 'Pizza Portuguesa', 'Pizza Frango com Catupiry', 'Picanha', 'Picanha Premium', 'Hambúrguer Artesanal', 'Açaí 500ml', 'Refrigerante Lata', 'Batata Frita'];
    }

    private function onlyExistingColumns(string $table, array $data): array
    {
        return array_filter(
            $data,
            fn ($value, $column) => Schema::hasColumn($table, $column),
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function idadeSql(): string
    {
        return "CAST((strftime('%Y', 'now') - strftime('%Y', c.cli_data_nascimento) - (strftime('%m-%d', 'now') < strftime('%m-%d', c.cli_data_nascimento))) AS INTEGER)";
    }
}
