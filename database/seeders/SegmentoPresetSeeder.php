<?php

namespace Database\Seeders;

use App\Models\SegmentoClientePreset;
use Illuminate\Database\Seeder;

class SegmentoPresetSeeder extends Seeder
{
    public function run(): void
    {
        $presets = [
            [
                'nome' => 'Clientes que compraram hoje',
                'descricao' => 'Clientes com último pedido confirmado no dia atual. Dinâmico: muda conforme o dia da consulta.',
                'categoria' => 'Compras recentes',
                'ordem' => 10,
                'regra_json' => [
                    'version' => 1,
                    'entity' => 'cliente',
                    'logic' => 'AND',
                    'conditions' => [
                        ['field' => 'ultimo_pedido', 'operator' => 'today', 'value' => null],
                    ],
                    'limit' => 25,
                    'order' => ['field' => 'ultimo_pedido_desc', 'direction' => 'desc'],
                    'resumo_humano' => 'Clientes que fizeram pedido confirmado hoje.',
                ],
            ],
            [
                'nome' => 'Clientes que compraram ontem',
                'descricao' => 'Clientes com último pedido confirmado ontem.',
                'categoria' => 'Compras recentes',
                'ordem' => 20,
                'regra_json' => [
                    'version' => 1,
                    'entity' => 'cliente',
                    'logic' => 'AND',
                    'conditions' => [
                        ['field' => 'ultimo_pedido', 'operator' => 'yesterday', 'value' => null],
                    ],
                    'limit' => 25,
                    'order' => ['field' => 'ultimo_pedido_desc', 'direction' => 'desc'],
                    'resumo_humano' => 'Clientes que fizeram pedido confirmado ontem.',
                ],
            ],
            [
                'nome' => 'Clientes sem comprar há 30 dias',
                'descricao' => 'Clientes que não compram há mais de 30 dias.',
                'categoria' => 'Recência',
                'ordem' => 30,
                'regra_json' => [
                    'version' => 1,
                    'entity' => 'cliente',
                    'logic' => 'AND',
                    'conditions' => [
                        ['field' => 'ultimo_pedido', 'operator' => 'more_than_x_days_ago', 'value' => 30],
                    ],
                    'limit' => 25,
                    'order' => ['field' => 'ultimo_pedido_asc', 'direction' => 'asc'],
                    'resumo_humano' => 'Clientes cujo último pedido confirmado foi há mais de 30 dias.',
                ],
            ],
            [
                'nome' => 'Top 10 clientes com mais pedidos',
                'descricao' => 'Ranking dos clientes com maior quantidade de pedidos confirmados.',
                'categoria' => 'Ranking',
                'ordem' => 40,
                'regra_json' => [
                    'version' => 1,
                    'entity' => 'cliente',
                    'logic' => 'AND',
                    'conditions' => [
                        ['field' => 'qtd_pedidos', 'operator' => 'greater_than', 'value' => 0],
                    ],
                    'limit' => 10,
                    'order' => ['field' => 'qtd_pedidos', 'direction' => 'desc'],
                    'resumo_humano' => 'Ranking dos 10 clientes com mais pedidos confirmados.',
                ],
            ],
            [
                'nome' => 'Clientes com cashback',
                'descricao' => 'Clientes com saldo de cashback maior que zero.',
                'categoria' => 'Fidelidade',
                'ordem' => 50,
                'regra_json' => [
                    'version' => 1,
                    'entity' => 'cliente',
                    'logic' => 'AND',
                    'conditions' => [
                        ['field' => 'cashback', 'operator' => 'greater_than', 'value' => 0],
                    ],
                    'limit' => 25,
                    'order' => ['field' => 'random', 'direction' => 'asc'],
                    'resumo_humano' => 'Clientes com cashback disponível.',
                ],
            ],
            [
                'nome' => 'Clientes com pontos',
                'descricao' => 'Clientes com pontuação total maior que zero.',
                'categoria' => 'Fidelidade',
                'ordem' => 60,
                'regra_json' => [
                    'version' => 1,
                    'entity' => 'cliente',
                    'logic' => 'AND',
                    'conditions' => [
                        ['field' => 'pontos_totais', 'operator' => 'greater_than', 'value' => 0],
                    ],
                    'limit' => 25,
                    'order' => ['field' => 'random', 'direction' => 'asc'],
                    'resumo_humano' => 'Clientes que possuem pontos acumulados.',
                ],
            ],
            [
                'nome' => 'Aniversariantes de hoje',
                'descricao' => 'Clientes que fazem aniversário hoje, usando mês e dia da data atual.',
                'categoria' => 'Datas especiais',
                'ordem' => 70,
                'regra_json' => [
                    'version' => 1,
                    'entity' => 'cliente',
                    'logic' => 'AND',
                    'conditions' => [
                        ['field' => 'nascimento', 'operator' => 'today', 'value' => null],
                    ],
                    'limit' => 25,
                    'order' => ['field' => 'random', 'direction' => 'asc'],
                    'resumo_humano' => 'Clientes que fazem aniversário hoje.',
                ],
            ],
            [
                'nome' => 'Clientes cadastrados nos últimos 7 dias',
                'descricao' => 'Clientes novos cadastrados nos últimos 7 dias.',
                'categoria' => 'Cadastro',
                'ordem' => 80,
                'regra_json' => [
                    'version' => 1,
                    'entity' => 'cliente',
                    'logic' => 'AND',
                    'conditions' => [
                        ['field' => 'data_cadastro', 'operator' => 'last_x_days', 'value' => 7],
                    ],
                    'limit' => 25,
                    'order' => ['field' => 'data_cadastro', 'direction' => 'desc'],
                    'resumo_humano' => 'Clientes cadastrados recentemente.',
                ],
            ],
            [
                'nome' => 'Clientes com newsletter ativa',
                'descricao' => 'Clientes que aceitaram receber comunicações.',
                'categoria' => 'Comunicação',
                'ordem' => 90,
                'regra_json' => [
                    'version' => 1,
                    'entity' => 'cliente',
                    'logic' => 'AND',
                    'conditions' => [
                        ['field' => 'newsletter', 'operator' => 'is_true', 'value' => null],
                    ],
                    'limit' => 25,
                    'order' => ['field' => 'random', 'direction' => 'asc'],
                    'resumo_humano' => 'Clientes que aceitaram receber comunicações.',
                ],
            ],
            [
                'nome' => 'Clientes de Feira de Santana',
                'descricao' => 'Modelo de segmentação por município/cidade.',
                'categoria' => 'Localização',
                'ordem' => 100,
                'regra_json' => [
                    'version' => 1,
                    'entity' => 'cliente',
                    'logic' => 'AND',
                    'conditions' => [
                        ['field' => 'municipio', 'operator' => 'equals', 'value' => 'Feira de Santana'],
                    ],
                    'limit' => 25,
                    'order' => ['field' => 'random', 'direction' => 'asc'],
                    'resumo_humano' => 'Clientes da cidade Feira de Santana.',
                ],
            ],
        ];

        $nomesAtivos = array_column($presets, 'nome');

        foreach ($presets as $preset) {
            SegmentoClientePreset::updateOrCreate(
                ['nome' => $preset['nome']],
                [
                    'descricao' => $preset['descricao'],
                    'categoria' => $preset['categoria'],
                    'ordem' => $preset['ordem'],
                    'regra_json' => $preset['regra_json'],
                    'ativo' => 'S',
                ]
            );
        }

        SegmentoClientePreset::whereNotIn('nome', $nomesAtivos)->update(['ativo' => 'N']);
    }
}
