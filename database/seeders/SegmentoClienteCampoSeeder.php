<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SegmentoClienteCampoSeeder extends Seeder
{
    public function run(): void
    {
        $campos = [
            [
                'chave' => 'nome_cliente',
                'label' => 'Nome do cliente',
                'descricao' => 'Nome cadastrado do cliente',
                'categoria' => 'cliente',
                'tipo_valor' => 'string',
                'origem_tabela' => 'cliente',
                'origem_coluna' => 'cli_nome',
                'expressao_sql' => 'cliente.cli_nome',
                'operadores_json' => json_encode(['contains', 'not_contains', 'starts_with', 'ends_with', 'is_empty', 'is_not_empty'], JSON_UNESCAPED_UNICODE),
                'ativo' => 'S',
                'ordem' => 10
            ],
            [
                'chave' => 'telefone_cliente',
                'label' => 'Telefone do cliente',
                'descricao' => 'Telefone/celular do cliente',
                'categoria' => 'cliente',
                'tipo_valor' => 'string',
                'origem_tabela' => 'cliente',
                'origem_coluna' => 'cli_telefone',
                'expressao_sql' => 'cliente.cli_telefone',
                'operadores_json' => json_encode(['contains', 'not_contains', 'starts_with', 'ends_with', 'is_empty', 'is_not_empty'], JSON_UNESCAPED_UNICODE),
                'ativo' => 'S',
                'ordem' => 20
            ],
            [
                'chave' => 'email_cliente',
                'label' => 'E-mail do cliente',
                'descricao' => 'E-mail cadastrado do cliente',
                'categoria' => 'cliente',
                'tipo_valor' => 'string',
                'origem_tabela' => 'cliente',
                'origem_coluna' => 'cli_email',
                'expressao_sql' => 'cliente.cli_email',
                'operadores_json' => json_encode(['contains', 'not_contains', 'starts_with', 'ends_with', 'is_empty', 'is_not_empty'], JSON_UNESCAPED_UNICODE),
                'ativo' => 'S',
                'ordem' => 30
            ],
            [
                'chave' => 'data_cadastro',
                'label' => 'Data de cadastro',
                'descricao' => 'Data em que o cliente foi cadastrado',
                'categoria' => 'cliente',
                'tipo_valor' => 'datetime',
                'origem_tabela' => 'cliente',
                'origem_coluna' => 'cadastrado',
                'expressao_sql' => 'cliente.cadastrado',
                'operadores_json' => json_encode(['today', 'equals_date', 'before_date', 'after_date', 'between_dates', 'last_x_days', 'more_than_x_days_ago', 'less_than_x_days_ago'], JSON_UNESCAPED_UNICODE),
                'ativo' => 'S',
                'ordem' => 40
            ],
            [
                'chave' => 'aniversario',
                'label' => 'Aniversário',
                'descricao' => 'Aniversário calculado pelo mês e dia do nascimento',
                'categoria' => 'cliente',
                'tipo_valor' => 'date',
                'origem_tabela' => 'cliente',
                'origem_coluna' => 'cli_data_nascimento',
                'expressao_sql' => 'view_cliente_contato.con_data_aniversario',
                'operadores_json' => json_encode(['today', 'equals_date', 'next_x_days', 'between_dates'], JSON_UNESCAPED_UNICODE),
                'ativo' => 'S',
                'ordem' => 50
            ],
            [
                'chave' => 'qtd_pedidos',
                'label' => 'Quantidade de pedidos',
                'descricao' => 'Quantidade total de pedidos gravada no cliente',
                'categoria' => 'pedido',
                'tipo_valor' => 'number',
                'origem_tabela' => 'cliente',
                'origem_coluna' => 'cli_qtd_pedidos',
                'expressao_sql' => 'cliente.cli_qtd_pedidos',
                'operadores_json' => json_encode(['equals', 'not_equals', 'greater_than', 'greater_or_equal', 'less_than', 'less_or_equal', 'between'], JSON_UNESCAPED_UNICODE),
                'ativo' => 'S',
                'ordem' => 60
            ],
            [
                'chave' => 'qtd_pedidos_confirmados',
                'label' => 'Quantidade de pedidos confirmados',
                'descricao' => 'Quantidade de pedidos confirmados, considerando status.sta_confirmado = S e pedido.excluido IS NULL',
                'categoria' => 'pedido',
                'tipo_valor' => 'number',
                'origem_tabela' => 'pedido',
                'origem_coluna' => 'pedido_id',
                'expressao_sql' => '(SELECT COUNT(*) FROM pedido p INNER JOIN status s ON s.status_id = p.status_id WHERE p.cliente_id = cliente.cliente_id AND p.excluido IS NULL AND s.sta_confirmado = \'S\')',
                'operadores_json' => json_encode(['equals', 'not_equals', 'greater_than', 'greater_or_equal', 'less_than', 'less_or_equal', 'between'], JSON_UNESCAPED_UNICODE),
                'ativo' => 'S',
                'ordem' => 70
            ],
            [
                'chave' => 'ultima_compra',
                'label' => 'Última compra',
                'descricao' => 'Data da última compra confirmada do cliente',
                'categoria' => 'pedido',
                'tipo_valor' => 'datetime',
                'origem_tabela' => 'pedido',
                'origem_coluna' => 'ped_data',
                'expressao_sql' => '(SELECT MAX(p.ped_data) FROM pedido p INNER JOIN status s ON s.status_id = p.status_id WHERE p.cliente_id = cliente.cliente_id AND p.excluido IS NULL AND s.sta_confirmado = \'S\')',
                'operadores_json' => json_encode(['equals_date', 'before_date', 'after_date', 'between_dates', 'last_x_days', 'exactly_x_days_ago', 'more_than_x_days_ago', 'less_than_x_days_ago', 'is_empty', 'is_not_empty'], JSON_UNESCAPED_UNICODE),
                'ativo' => 'S',
                'ordem' => 80
            ],
            [
                'chave' => 'primeira_compra',
                'label' => 'Primeira compra',
                'descricao' => 'Data da primeira compra confirmada do cliente',
                'categoria' => 'pedido',
                'tipo_valor' => 'datetime',
                'origem_tabela' => 'pedido',
                'origem_coluna' => 'ped_data',
                'expressao_sql' => '(SELECT MIN(p.ped_data) FROM pedido p INNER JOIN status s ON s.status_id = p.status_id WHERE p.cliente_id = cliente.cliente_id AND p.excluido IS NULL AND s.sta_confirmado = \'S\')',
                'operadores_json' => json_encode(['equals_date', 'before_date', 'after_date', 'between_dates', 'last_x_days', 'more_than_x_days_ago', 'less_than_x_days_ago', 'is_empty', 'is_not_empty'], JSON_UNESCAPED_UNICODE),
                'ativo' => 'S',
                'ordem' => 90
            ],
            [
                'chave' => 'valor_total_compras',
                'label' => 'Valor total comprado',
                'descricao' => 'Soma dos valores de pedidos confirmados',
                'categoria' => 'pedido',
                'tipo_valor' => 'money',
                'origem_tabela' => 'pedido',
                'origem_coluna' => 'ped_valor_total',
                'expressao_sql' => '(SELECT COALESCE(SUM(p.ped_valor_total),0) FROM pedido p INNER JOIN status s ON s.status_id = p.status_id WHERE p.cliente_id = cliente.cliente_id AND p.excluido IS NULL AND s.sta_confirmado = \'S\')',
                'operadores_json' => json_encode(['equals', 'not_equals', 'greater_than', 'greater_or_equal', 'less_than', 'less_or_equal', 'between'], JSON_UNESCAPED_UNICODE),
                'ativo' => 'S',
                'ordem' => 100
            ],
            [
                'chave' => 'cashback_saldo',
                'label' => 'Saldo de cashback',
                'descricao' => 'Saldo total de cashback disponível do cliente',
                'categoria' => 'cashback',
                'tipo_valor' => 'money',
                'origem_tabela' => 'cashback',
                'origem_coluna' => 'cas_valor',
                'expressao_sql' => '(SELECT COALESCE(SUM(cas_valor),0) FROM cashback cb WHERE cb.cliente_id = cliente.cliente_id AND cb.excluido IS NULL)',
                'operadores_json' => json_encode(['equals', 'not_equals', 'greater_than', 'greater_or_equal', 'less_than', 'less_or_equal', 'between'], JSON_UNESCAPED_UNICODE),
                'ativo' => 'S',
                'ordem' => 110
            ],
            [
                'chave' => 'proxima_compra',
                'label' => 'Próxima compra prevista',
                'descricao' => 'Data prevista para próxima compra do cliente',
                'categoria' => 'cliente',
                'tipo_valor' => 'datetime',
                'origem_tabela' => 'cliente',
                'origem_coluna' => 'cli_proxima_compra',
                'expressao_sql' => 'cliente.cli_proxima_compra',
                'operadores_json' => json_encode(['equals_date', 'before_date', 'after_date', 'between_dates', 'next_x_days', 'last_x_days', 'is_empty', 'is_not_empty'], JSON_UNESCAPED_UNICODE),
                'ativo' => 'S',
                'ordem' => 120
            ],
            [
                'chave' => 'recebeu_notificacao_nos_ultimos_dias',
                'label' => 'Recebeu notificação recentemente',
                'descricao' => 'Verifica se o cliente recebeu notificação nos últimos X dias',
                'categoria' => 'notificacao',
                'tipo_valor' => 'number',
                'origem_tabela' => 'notificacao_programada_envio',
                'origem_coluna' => 'cadastrado',
                'expressao_sql' => '(SELECT COUNT(*) FROM notificacao_programada_envio npe WHERE npe.cliente_id = cliente.cliente_id AND npe.excluido IS NULL AND npe.cadastrado >= DATE_SUB(NOW(), INTERVAL ? DAY))',
                'operadores_json' => json_encode(['exists', 'not_exists'], JSON_UNESCAPED_UNICODE),
                'ativo' => 'S',
                'ordem' => 130
            ],
            [
                'chave' => 'produto_comprado',
                'label' => 'Produto comprado',
                'descricao' => 'Verifica se o cliente comprou produto específico',
                'categoria' => 'produto',
                'tipo_valor' => 'number',
                'origem_tabela' => 'pedido_item',
                'origem_coluna' => 'produto_id',
                'expressao_sql' => '(SELECT COUNT(*) FROM pedido_item pi INNER JOIN pedido p ON p.pedido_id = pi.pedido_id INNER JOIN status s ON s.status_id = p.status_id WHERE p.cliente_id = cliente.cliente_id AND pi.excluido IS NULL AND p.excluido IS NULL AND s.sta_confirmado = \'S\' AND pi.produto_id = ?)',
                'operadores_json' => json_encode(['exists', 'not_exists'], JSON_UNESCAPED_UNICODE),
                'ativo' => 'S',
                'ordem' => 140
            ]
        ];

        foreach ($campos as $campo) {
            DB::table('segmento_cliente_campo')->updateOrInsert(
                ['chave' => $campo['chave']],
                $campo
            );
        }
    }
}
