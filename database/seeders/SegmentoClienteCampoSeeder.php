<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SegmentoClienteCampoSeeder extends Seeder
{
    public function run(): void
    {
        $opsTexto = ['equals', 'not_equals', 'contains', 'not_contains', 'starts_with', 'ends_with', 'is_empty', 'is_not_empty'];
        $opsNumero = ['equals', 'not_equals', 'greater_than', 'greater_or_equal', 'less_than', 'less_or_equal', 'between'];
        $opsData = ['today', 'yesterday', 'equals_date', 'before_date', 'after_date', 'between_dates', 'last_x_days', 'next_x_days', 'exactly_x_days_ago', 'more_than_x_days_ago', 'less_than_x_days_ago', 'month_equals', 'month_between', 'is_empty', 'is_not_empty'];
        $opsBool = ['equals', 'not_equals', 'is_true', 'is_false'];
        $opsProduto = ['contains', 'not_contains', 'exists', 'not_exists'];

        $campos = [
            $this->campo('nome', 'Nome do cliente', 'Nome cadastrado do cliente', 'cliente', 'string', 'cliente', 'cli_nome', 'c.cli_nome', $opsTexto, 10),
            $this->campo('nome_cliente', 'Nome do cliente', 'Alias para nome do cliente', 'cliente', 'string', 'cliente', 'cli_nome', 'c.cli_nome', $opsTexto, 11),
            $this->campo('cpf', 'CPF do cliente', 'CPF cadastrado do cliente', 'cliente', 'string', 'cliente', 'cli_cpf', 'c.cli_cpf', $opsTexto, 20),
            $this->campo('telefone', 'Telefone do cliente', 'Telefone/celular do cliente', 'cliente', 'string', 'cliente', 'cli_telefone', 'c.cli_telefone', $opsTexto, 30),
            $this->campo('telefone_cliente', 'Telefone do cliente', 'Alias para telefone do cliente', 'cliente', 'string', 'cliente', 'cli_telefone', 'c.cli_telefone', $opsTexto, 31),
            $this->campo('email', 'E-mail do cliente', 'E-mail cadastrado do cliente', 'cliente', 'string', 'cliente', 'cli_email', 'c.cli_email', $opsTexto, 40),
            $this->campo('email_cliente', 'E-mail do cliente', 'Alias para e-mail do cliente', 'cliente', 'string', 'cliente', 'cli_email', 'c.cli_email', $opsTexto, 41),

            $this->campo('sexo', 'Sexo', 'Sexo cadastrado do cliente', 'cliente', 'select', 'cliente', 'sexo_id', 'c.sexo_id', ['equals', 'not_equals', 'is_empty', 'is_not_empty'], 50),
            $this->campo('nascimento', 'Data de nascimento', 'Data de nascimento do cliente', 'cliente', 'date', 'cliente', 'cli_data_nascimento', 'c.cli_data_nascimento', $opsData, 60),
            $this->campo('aniversario', 'Aniversário', 'Aniversário calculado pelo mês e dia do nascimento', 'cliente', 'date', 'cliente', 'cli_data_nascimento', 'c.cli_data_nascimento', ['today', 'equals_date', 'next_x_days', 'between_dates', 'month_equals', 'month_between'], 61),
            $this->campo('idade', 'Idade', 'Idade calculada pela data de nascimento', 'cliente', 'number', 'cliente', 'cli_data_nascimento', $this->idadeSql(), $opsNumero, 70),

            $this->campo('bairro', 'Bairro', 'Bairro cadastrado do cliente', 'endereco', 'string', 'cliente', 'cli_bairro', 'c.cli_bairro', $opsTexto, 80),
            $this->campo('municipio', 'Município', 'Cidade/município cadastrado do cliente', 'endereco', 'string', 'cliente', 'cli_cidade', 'c.cli_cidade', $opsTexto, 90),
            $this->campo('cidade', 'Cidade', 'Alias para município/cidade do cliente', 'endereco', 'string', 'cliente', 'cli_cidade', 'c.cli_cidade', $opsTexto, 91),
            $this->campo('estado', 'Estado (UF)', 'Estado/UF cadastrado do cliente', 'endereco', 'string', 'cliente', 'cli_estado', 'c.cli_estado', $opsTexto, 100),

            $this->campo('funcionario', 'Funcionário(a)', 'Indica se o cliente é funcionário da empresa', 'cliente', 'boolean', 'cliente', 'cli_funcionario', 'c.cli_funcionario', $opsBool, 110),
            $this->campo('newsletter', 'Newsletter', 'Indica se o cliente aceita receber comunicações', 'cliente', 'boolean', 'cliente', 'cli_newsletter', 'c.cli_newsletter', $opsBool, 120),

            $this->campo('data_cadastro', 'Data de cadastro', 'Data em que o cliente foi cadastrado', 'cliente', 'datetime', 'cliente', 'cadastrado', 'c.cadastrado', $opsData, 130),
            $this->campo('qtd_pedidos', 'Quantidade de pedidos', 'Quantidade total de pedidos confirmados', 'pedido', 'number', 'pedido', 'pedido_id', 'COALESCE(ps.qtd_pedidos, 0)', $opsNumero, 140),
            $this->campo('qtd_pedidos_confirmados', 'Quantidade de pedidos confirmados', 'Alias para quantidade de pedidos confirmados', 'pedido', 'number', 'pedido', 'pedido_id', 'COALESCE(ps.qtd_pedidos, 0)', $opsNumero, 141),
            $this->campo('ultimo_pedido', 'Última compra', 'Data da última compra confirmada', 'pedido', 'datetime', 'pedido', 'ped_data', 'ps.ultimo_pedido', $opsData, 150),
            $this->campo('ultima_compra', 'Última compra', 'Alias para última compra confirmada', 'pedido', 'datetime', 'pedido', 'ped_data', 'ps.ultimo_pedido', $opsData, 151),
            $this->campo('primeira_compra', 'Primeira compra', 'Data da primeira compra confirmada', 'pedido', 'datetime', 'pedido', 'ped_data', 'ps.primeira_compra', $opsData, 160),
            $this->campo('valor_total_comprado', 'Valor total comprado', 'Soma dos valores de pedidos confirmados', 'pedido', 'money', 'pedido', 'ped_valor_total', 'COALESCE(ps.valor_total_comprado, 0)', $opsNumero, 170),
            $this->campo('valor_total_compras', 'Valor total comprado', 'Alias para valor total comprado', 'pedido', 'money', 'pedido', 'ped_valor_total', 'COALESCE(ps.valor_total_comprado, 0)', $opsNumero, 171),

            $this->campo('cashback', 'Saldo de cashback', 'Saldo total de cashback disponível do cliente', 'cashback', 'money', 'cashback', 'cas_valor', 'COALESCE(cb.cashback, 0)', $opsNumero, 180),
            $this->campo('cashback_saldo', 'Saldo de cashback', 'Alias para saldo de cashback', 'cashback', 'money', 'cashback', 'cas_valor', 'COALESCE(cb.cashback, 0)', $opsNumero, 181),
            $this->campo('cashback_expira_em', 'Cashback expira em', 'Data estimada de expiração do cashback', 'cashback', 'datetime', 'cashback', 'cadastrado', 'cb.cashback_expira_em', $opsData, 190),
            $this->campo('pontos_totais', 'Pontos totais', 'Pontos acumulados do cliente', 'cliente', 'number', 'cliente', 'cli_pontos_totais', 'COALESCE(c.cli_pontos_totais, 0)', $opsNumero, 200),

            $this->campo('carrinho_abandonado', 'Carrinho abandonado', 'Indica se o cliente possui carrinho abandonado', 'carrinho', 'boolean', 'cliente', 'cliente_id', 'NULL', ['is_true', 'is_false', 'exists', 'not_exists'], 210),
            $this->campo('produto_comprado', 'Produto comprado', 'Produto comprado pelo cliente', 'produto', 'string', 'produto', 'pro_nome', 'prod.produtos_comprados', $opsProduto, 220),
            $this->campo('produto_nome', 'Produto comprado', 'Alias para produto comprado', 'produto', 'string', 'produto', 'pro_nome', 'prod.produtos_comprados', $opsProduto, 221),
            $this->campo('produto', 'Produto comprado', 'Alias curto para produto comprado', 'produto', 'string', 'produto', 'pro_nome', 'prod.produtos_comprados', $opsProduto, 222),

            $this->campo('origem_contato', 'Origem do contato', 'Origem do contato/cliente', 'contato', 'string', 'cliente', 'cliente_origem_id', 'CAST(c.cliente_origem_id AS CHAR)', $opsTexto, 230),
            $this->campo('recebeu_notificacao_nos_ultimos_dias', 'Recebeu notificação recentemente', 'Verifica se o cliente recebeu notificação recentemente', 'notificacao', 'number', 'notificacao_programada_envio', 'cadastrado', 'npe.cadastrado', ['exists', 'not_exists', 'last_x_days', 'less_than_x_days_ago'], 240),
            $this->campo('busca_geral', 'Busca geral', 'Busca em nome, telefone, e-mail e CPF', 'sistema', 'string', 'cliente', null, "COALESCE(c.cli_nome, '') || ' ' || COALESCE(c.cli_telefone, '') || ' ' || COALESCE(c.cli_email, '') || ' ' || COALESCE(c.cli_cpf, '')", ['contains', 'not_contains', 'starts_with', 'ends_with'], 250),
        ];

        foreach ($campos as $campo) {
            DB::table('segmento_cliente_campo')->updateOrInsert(
                ['chave' => $campo['chave']],
                $campo
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
        int $ordem
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
            'ativo' => 'S',
            'ordem' => $ordem,
        ];
    }

    private function idadeSql(): string
    {
        return "CAST((strftime('%Y', 'now') - strftime('%Y', c.cli_data_nascimento) - (strftime('%m-%d', 'now') < strftime('%m-%d', c.cli_data_nascimento))) AS INTEGER)";
    }
}
