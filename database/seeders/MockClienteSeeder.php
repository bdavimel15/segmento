<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MockClienteSeeder extends Seeder
{
    public function run(): void
    {
        $this->limparMocksAntigos();

        $statusConfirmadoId = DB::table('status')->insertGetId($this->onlyExistingColumns('status', [
            'sta_nome' => 'Confirmado Mock',
            'sta_confirmado' => 'S',
            'sta_ativo' => 'S',
            'excluido' => null,
            'cadastrado' => now(),
            'atualizado' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $statusCanceladoId = DB::table('status')->insertGetId($this->onlyExistingColumns('status', [
            'sta_nome' => 'Cancelado Mock',
            'sta_confirmado' => 'N',
            'sta_ativo' => 'S',
            'excluido' => null,
            'cadastrado' => now(),
            'atualizado' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $produtos = $this->criarProdutosMock();

        $clientes = [
            ['Ana Silva', 'F', 45, [0, 50, 80, 120], 42.50],
            ['Bruno Costa', 'M', 70, [31, 65], 15.00],
            ['Carla Santos', 'F', 10, [0, 5, 12, 18], 0],
            ['Diego Almeida', 'M', 120, [35, 90, 130, 170], 80.00],
            ['Eduarda Lima', 'F', 5, [2], 0],
            ['Felipe Rocha', 'M', 200, [40, 75, 110], 22.90],
            ['Gabriela Souza', 'F', 25, [32], 0],
            ['Henrique Martins', 'M', 60, [33, 34, 95], 60.00],
            ['Isabela Nunes', 'F', 15, [1, 7], 0],
            ['João Pereira', 'M', 90, [45, 60], 10.00],
            ['Karen Oliveira', 'F', 140, [], 0],
            ['Lucas Ferreira', 'M', 30, [31, 45, 70], 35.00],
            ['Mariana Gomes', 'F', 75, [29], 0],
            ['Nicolas Barbosa', 'M', 300, [100, 150, 210], 55.00],
            ['Patrícia Ribeiro', 'F', 18, [3, 8, 14], 0],
            ['Rafael Mendes', 'M', 66, [36], 18.00],
            ['Sofia Cardoso', 'F', 110, [44, 88, 132], 27.00],
            ['Thiago Alves', 'M', 7, [4], 0],
            ['Valentina Moreira', 'F', 220, [55, 95], 12.00],
            ['Yasmin Castro', 'F', 50, [30, 31, 61], 33.00],
        ];

        $cidades = [
            ['Feira de Santana', 'BA', 'Centro'],
            ['Salvador', 'BA', 'Barra'],
            ['São Paulo', 'SP', 'Pinheiros'],
            ['Rio de Janeiro', 'RJ', 'Copacabana'],
            ['Belo Horizonte', 'MG', 'Savassi'],
        ];

        $canaisPedido = ['WhatsApp', 'Balcão', 'Delivery próprio', 'iFood', 'Site', 'Instagram'];
        $formasPagamento = ['Pix', 'Cartão de crédito', 'Cartão de débito', 'Dinheiro', 'Vale refeição'];

        foreach ($clientes as $index => [$nome, $sexo, $cadastroDiasAtras, $comprasDiasAtras, $cashback]) {
            [$cidade, $estado, $bairro] = $cidades[$index % count($cidades)];
            $createdAt = Carbon::now()->subDays($cadastroDiasAtras);

            $clienteId = DB::table('cliente')->insertGetId($this->onlyExistingColumns('cliente', [
                'cli_nome' => $nome,
                'cli_cpf' => '00000000' . str_pad((string)($index + 1), 3, '0', STR_PAD_LEFT),
                'sexo_id' => $sexo,
                'cli_telefone' => '55999900' . str_pad((string)($index + 1), 3, '0', STR_PAD_LEFT),
                'cli_email' => 'cliente' . ($index + 1) . '@mock.local',
                'cli_cidade' => $cidade,
                'cli_estado' => $estado,
                'cli_bairro' => $bairro,
                'cli_data_nascimento' => Carbon::now()->subYears(20 + ($index % 25))->subDays($index)->toDateString(),
                'cli_qtd_pedidos' => count($comprasDiasAtras),
                'cli_proxima_compra' => Carbon::now()->addDays(7 + ($index % 20)),
                'cli_newsletter' => $index % 4 === 0 ? 'N' : 'S',
                'cli_funcionario' => $index % 7 === 0 ? 'S' : 'N',
                'cli_pontos_totais' => 10 + ($index * 17),
                'cliente_origem_id' => ($index % 3) + 1,
                'excluido' => null,
                'cadastrado' => $createdAt,
                'atualizado' => now(),
                'created_at' => $createdAt,
                'updated_at' => now(),
            ]));

            foreach ($comprasDiasAtras as $pedidoIndex => $diasAtras) {
                $pedidoData = Carbon::now()->subDays($diasAtras);
                $pedidoId = DB::table('pedido')->insertGetId($this->onlyExistingColumns('pedido', [
                    'ped_numero' => 'MOCK-' . $clienteId . '-' . ($pedidoIndex + 1),
                    'ped_data' => $pedidoData,
                    'cliente_id' => $clienteId,
                    'estabelecimento_id' => 1,
                    'status_id' => $statusConfirmadoId,
                    'ped_valor_total' => 49.90 + ($pedidoIndex * 25),
                    'canal_pedido' => $canaisPedido[($index + $pedidoIndex) % count($canaisPedido)],
                    'forma_pagamento' => $formasPagamento[($index + $pedidoIndex) % count($formasPagamento)],
                    'excluido' => null,
                    'cadastrado' => $pedidoData,
                    'atualizado' => now(),
                    'created_at' => $pedidoData,
                    'updated_at' => now(),
                ]));

                $this->criarItensDoPedidoMock($pedidoId, $pedidoIndex, $index, $produtos);

                if ($cashback > 0 && $pedidoIndex === 0) {
                    DB::table('cashback')->insert($this->onlyExistingColumns('cashback', [
                        'cliente_id' => $clienteId,
                        'pedido_id' => $pedidoId,
                        'cas_valor' => $cashback,
                        'cas_complemento' => 'Cashback mock para testes',
                        'excluido' => null,
                        'cadastrado' => $pedidoData,
                        'atualizado' => now(),
                        'created_at' => $pedidoData,
                        'updated_at' => now(),
                    ]));
                }
            }

            if ($index % 6 === 0) {
                DB::table('pedido')->insert($this->onlyExistingColumns('pedido', [
                    'ped_numero' => 'MOCK-CANCEL-' . $clienteId,
                    'ped_data' => Carbon::now()->subDays(2),
                    'cliente_id' => $clienteId,
                    'estabelecimento_id' => 1,
                    'status_id' => $statusCanceladoId,
                    'ped_valor_total' => 99.90,
                    'canal_pedido' => $canaisPedido[$index % count($canaisPedido)],
                    'forma_pagamento' => $formasPagamento[$index % count($formasPagamento)],
                    'excluido' => null,
                    'cadastrado' => Carbon::now()->subDays(2),
                    'atualizado' => now(),
                    'created_at' => Carbon::now()->subDays(2),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    private function onlyExistingColumns(string $table, array $data): array
    {
        return array_filter(
            $data,
            fn ($value, $column) => Schema::hasColumn($table, $column),
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function limparMocksAntigos(): void
    {
        if (!Schema::hasTable('cliente')) {
            return;
        }

        $ids = DB::table('cliente')
            ->when(Schema::hasColumn('cliente', 'cli_email'), fn ($q) => $q->where('cli_email', 'like', '%@mock.local'))
            ->pluck('cliente_id')
            ->all();

        if ($ids !== []) {
            if (Schema::hasTable('cashback')) {
                DB::table('cashback')->whereIn('cliente_id', $ids)->delete();
            }
            if (Schema::hasTable('pedido_item') && Schema::hasTable('pedido')) {
                $pedidoIds = DB::table('pedido')->whereIn('cliente_id', $ids)->pluck('pedido_id')->all();
                if ($pedidoIds !== []) {
                    DB::table('pedido_item')->whereIn('pedido_id', $pedidoIds)->delete();
                }
            }
            if (Schema::hasTable('pedido')) {
                DB::table('pedido')->whereIn('cliente_id', $ids)->delete();
            }
            DB::table('cliente')->whereIn('cliente_id', $ids)->delete();
        }

        if (Schema::hasTable('status') && Schema::hasColumn('status', 'sta_nome')) {
            DB::table('status')->where('sta_nome', 'like', '%Mock%')->delete();
        }

        if (Schema::hasTable('produto') && Schema::hasColumn('produto', 'pro_sku')) {
            DB::table('produto')->where('pro_sku', 'like', 'MOCK-%')->delete();
        }
    }

    private function criarProdutosMock(): array
    {
        if (!Schema::hasTable('produto')) {
            return [];
        }

        $produtos = [
            ['Picanha', 'MOCK-PICANHA', 89.90],
            ['Pizza Calabresa', 'MOCK-PIZZA-CALABRESA', 49.90],
            ['Hambúrguer Artesanal', 'MOCK-HAMBURGUER', 32.90],
            ['Açaí 500ml', 'MOCK-ACAI', 24.90],
            ['Refrigerante Lata', 'MOCK-REFRI', 7.90],
            ['Batata Frita', 'MOCK-BATATA', 18.90],
        ];

        $ids = [];

        foreach ($produtos as [$nome, $sku, $preco]) {
            $existente = DB::table('produto')
                ->when(Schema::hasColumn('produto', 'pro_sku'), fn ($q) => $q->where('pro_sku', $sku))
                ->value('produto_id');

            if ($existente) {
                $ids[] = (int)$existente;
                continue;
            }

            $ids[] = DB::table('produto')->insertGetId($this->onlyExistingColumns('produto', [
                'pro_nome' => $nome,
                'pro_sku' => $sku,
                'pro_preco' => $preco,
                'excluido' => null,
                'cadastrado' => now(),
                'atualizado' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        return $ids;
    }

    private function criarItensDoPedidoMock(int $pedidoId, int $pedidoIndex, int $clienteIndex, array $produtoIds): void
    {
        if (!Schema::hasTable('pedido_item') || $produtoIds === []) {
            return;
        }

        $produtoIdPrincipal = $produtoIds[($clienteIndex + $pedidoIndex) % count($produtoIds)];
        $produtoIdExtra = $produtoIds[($clienteIndex + $pedidoIndex + 2) % count($produtoIds)];

        foreach (array_unique([$produtoIdPrincipal, $produtoIdExtra]) as $posicao => $produtoId) {
            $quantidade = $posicao === 0 ? 1 : 2;
            $valorUnitario = 19.90 + (($clienteIndex + $pedidoIndex + $posicao) * 3);

            DB::table('pedido_item')->insert($this->onlyExistingColumns('pedido_item', [
                'pedido_id' => $pedidoId,
                'produto_id' => $produtoId,
                'pei_quantidade' => $quantidade,
                'pei_valor_unitario' => $valorUnitario,
                'pei_valor_total' => $valorUnitario * $quantidade,
                'excluido' => null,
                'cadastrado' => now(),
                'atualizado' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
