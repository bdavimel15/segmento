<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SegmentoClienteCampoSeeder extends Seeder
{
    public function run(): void
    {
        $table = 'segmento_cliente_campo';

        if (! Schema::hasTable($table)) {
            return;
        }

        $now = now();

        $campos = [
            ['chave'=>'nome','label'=>'Nome do cliente','descricao'=>'Nome cadastrado do cliente.','categoria'=>'cliente','tipo_valor'=>'string','expressao_sql'=>'c.cli_nome','operadores_json'=>['equals','not_equals','contains','not_contains','starts_with','ends_with','is_empty','is_not_empty'],'ordem'=>1],
            ['chave'=>'cpf','label'=>'CPF do cliente','descricao'=>'CPF cadastrado do cliente.','categoria'=>'cliente','tipo_valor'=>'string','expressao_sql'=>'c.cli_cpf','operadores_json'=>['equals','not_equals','contains','not_contains','is_empty','is_not_empty'],'ordem'=>2],
            ['chave'=>'sexo','label'=>'Sexo','descricao'=>'Sexo cadastrado do cliente.','categoria'=>'cliente','tipo_valor'=>'select','expressao_sql'=>"CASE WHEN c.sexo_id IN ('M','Masculino','masculino','homem','Homem') THEN 'Masculino' WHEN c.sexo_id IN ('F','Feminino','feminino','mulher','Mulher') THEN 'Feminino' ELSE c.sexo_id END",'operadores_json'=>['equals','not_equals','is_empty','is_not_empty'],'ordem'=>3],
            ['chave'=>'telefone','label'=>'Telefone do cliente','descricao'=>'Telefone/celular do cliente.','categoria'=>'cliente','tipo_valor'=>'string','expressao_sql'=>'c.cli_telefone','operadores_json'=>['equals','not_equals','contains','not_contains','is_empty','is_not_empty'],'ordem'=>4],
            ['chave'=>'email','label'=>'E-mail do cliente','descricao'=>'E-mail cadastrado do cliente.','categoria'=>'cliente','tipo_valor'=>'string','expressao_sql'=>'c.cli_email','operadores_json'=>['equals','not_equals','contains','not_contains','ends_with','is_empty','is_not_empty'],'ordem'=>5],
            ['chave'=>'nascimento','label'=>'Data de nascimento','descricao'=>'Data de nascimento do cliente.','categoria'=>'cliente','tipo_valor'=>'date','expressao_sql'=>'c.cli_data_nascimento','operadores_json'=>['today','yesterday','month_equals','month_between','equals_date','before_date','after_date'],'ordem'=>6],
            ['chave'=>'idade','label'=>'Idade','descricao'=>'Idade calculada pela data de nascimento.','categoria'=>'cliente','tipo_valor'=>'number','expressao_sql'=>'TIMESTAMPDIFF(YEAR, c.cli_data_nascimento, CURDATE())','operadores_json'=>['equals','not_equals','greater_than','greater_or_equal','less_than','less_or_equal','between'],'ordem'=>7],
            ['chave'=>'funcionario','label'=>'Funcionário(a)','descricao'=>'Indica se o cliente é funcionário.','categoria'=>'cliente','tipo_valor'=>'select','expressao_sql'=>"CASE WHEN c.cli_funcionario IN ('S','SIM','Sim','sim','1') THEN 'Sim' ELSE 'Não' END",'operadores_json'=>['equals','not_equals','is_true','is_false'],'ordem'=>8],
            ['chave'=>'newsletter','label'=>'Newsletter','descricao'=>'Indica se o cliente aceita receber comunicações.','categoria'=>'cliente','tipo_valor'=>'select','expressao_sql'=>"CASE WHEN c.cli_newsletter IN ('S','SIM','Sim','sim','1') THEN 'Sim' ELSE 'Não' END",'operadores_json'=>['equals','not_equals','is_true','is_false'],'ordem'=>9],
            ['chave'=>'data_cadastro','label'=>'Data de cadastro','descricao'=>'Data em que o cliente foi cadastrado.','categoria'=>'cliente','tipo_valor'=>'datetime','expressao_sql'=>'c.cadastrado','operadores_json'=>['today','yesterday','last_x_days','more_than_x_days_ago','less_than_x_days_ago','before_date','after_date','equals_date'],'ordem'=>10],
            ['chave'=>'bairro','label'=>'Bairro','descricao'=>'Bairro cadastrado do cliente.','categoria'=>'endereco','tipo_valor'=>'string','expressao_sql'=>'c.cli_bairro','operadores_json'=>['equals','not_equals','contains','not_contains','starts_with','ends_with','is_empty','is_not_empty'],'ordem'=>11],
            ['chave'=>'municipio','label'=>'Cidade','descricao'=>'Cidade/município cadastrado do cliente.','categoria'=>'endereco','tipo_valor'=>'string','expressao_sql'=>'c.cli_cidade','operadores_json'=>['equals','not_equals','contains','not_contains','starts_with','ends_with','is_empty','is_not_empty'],'ordem'=>12],
            ['chave'=>'estado','label'=>'Estado (UF)','descricao'=>'UF do cliente.','categoria'=>'endereco','tipo_valor'=>'select','expressao_sql'=>'c.cli_estado','operadores_json'=>['equals','not_equals','is_empty','is_not_empty'],'ordem'=>13],
            ['chave'=>'origem_contato','label'=>'Origem do contato','descricao'=>'Canal de origem do cliente.','categoria'=>'contato','tipo_valor'=>'select','expressao_sql'=>"CASE c.cliente_origem_id WHEN 1 THEN 'WhatsApp' WHEN 2 THEN 'Instagram' WHEN 3 THEN 'iFood' WHEN 4 THEN 'Balcão' WHEN 5 THEN 'Site' ELSE NULL END",'operadores_json'=>['equals','not_equals','is_empty','is_not_empty'],'ordem'=>14],
            ['chave'=>'qtd_pedidos','label'=>'Quantidade de pedidos confirmados','descricao'=>'Quantidade de pedidos confirmados do cliente.','categoria'=>'pedido','tipo_valor'=>'number','expressao_sql'=>'ps.qtd_pedidos','operadores_json'=>['equals','not_equals','greater_than','greater_or_equal','less_than','less_or_equal'],'ordem'=>20],
            ['chave'=>'ultimo_pedido','label'=>'Última compra','descricao'=>'Data da última compra confirmada.','categoria'=>'pedido','tipo_valor'=>'datetime','expressao_sql'=>'ps.ultimo_pedido','operadores_json'=>['today','yesterday','more_than_x_days_ago','less_than_x_days_ago','last_x_days','exactly_x_days_ago','before_date','after_date','equals_date'],'ordem'=>21],
            ['chave'=>'primeira_compra','label'=>'Primeira compra','descricao'=>'Data da primeira compra confirmada.','categoria'=>'pedido','tipo_valor'=>'datetime','expressao_sql'=>'ps.primeira_compra','operadores_json'=>['today','yesterday','last_x_days','more_than_x_days_ago','before_date','after_date','equals_date'],'ordem'=>22],
            ['chave'=>'valor_total_comprado','label'=>'Valor total comprado','descricao'=>'Soma dos valores de pedidos confirmados.','categoria'=>'pedido','tipo_valor'=>'money','expressao_sql'=>'ps.valor_total_comprado','operadores_json'=>['equals','not_equals','greater_than','greater_or_equal','less_than','less_or_equal'],'ordem'=>23],
            ['chave'=>'status_pedido','label'=>'Status do pedido','descricao'=>'Status dos pedidos do cliente.','categoria'=>'pedido','tipo_valor'=>'select','expressao_sql'=>'ps.status_pedido','operadores_json'=>['equals','not_equals','contains','not_contains','is_empty','is_not_empty'],'ordem'=>24],
            ['chave'=>'canal_pedido','label'=>'Canal do pedido','descricao'=>'Canal onde o pedido foi feito.','categoria'=>'pedido','tipo_valor'=>'select','expressao_sql'=>'ps.canal_pedido','operadores_json'=>['equals','not_equals','contains','not_contains','is_empty','is_not_empty'],'ordem'=>25],
            ['chave'=>'forma_pagamento','label'=>'Forma de pagamento','descricao'=>'Forma de pagamento usada no pedido.','categoria'=>'pedido','tipo_valor'=>'select','expressao_sql'=>'ps.forma_pagamento','operadores_json'=>['equals','not_equals','contains','not_contains','is_empty','is_not_empty'],'ordem'=>26],
            ['chave'=>'produto_comprado','label'=>'Produto comprado','descricao'=>'Produto comprado em algum pedido confirmado.','categoria'=>'produto','tipo_valor'=>'select','expressao_sql'=>'prod.produtos_comprados','operadores_json'=>['equals','not_equals','contains','not_contains','is_empty','is_not_empty'],'ordem'=>30],
            ['chave'=>'cashback','label'=>'Cashback','descricao'=>'Saldo de cashback do cliente.','categoria'=>'cashback','tipo_valor'=>'money','expressao_sql'=>'cb.cashback','operadores_json'=>['equals','not_equals','greater_than','greater_or_equal','less_than','less_or_equal'],'ordem'=>40],
            ['chave'=>'cashback_expira_em','label'=>'Cashback expira em','descricao'=>'Data estimada de expiração do cashback.','categoria'=>'cashback','tipo_valor'=>'datetime','expressao_sql'=>'cb.cashback_expira_em','operadores_json'=>['next_x_days','before_date','after_date','equals_date'],'ordem'=>41],
            ['chave'=>'pontos_totais','label'=>'Pontos totais','descricao'=>'Total de pontos do cliente.','categoria'=>'cliente','tipo_valor'=>'number','expressao_sql'=>'c.cli_pontos_totais','operadores_json'=>['equals','not_equals','greater_than','greater_or_equal','less_than','less_or_equal'],'ordem'=>42],
            ['chave'=>'carrinho_abandonado','label'=>'Carrinho abandonado','descricao'=>'Cliente presente na base de carrinho abandonado.','categoria'=>'carrinho','tipo_valor'=>'boolean','expressao_sql'=>'vca.cliente_id','operadores_json'=>['is_true','is_false','exists','not_exists'],'ordem'=>50],
        ];

        DB::table($table)->delete();

        foreach ($campos as $campo) {
            DB::table($table)->insert($campo + [
                'ativo' => 'S',
                'cadastrado' => $now,
                'atualizado' => $now,
            ]);
        }
    }
}
