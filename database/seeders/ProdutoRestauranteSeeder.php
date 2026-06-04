<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProdutoRestauranteSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('produtos')) {
            return;
        }

        $now = now();

        $produtos = [
            ['nome' => 'Pizza Calabresa', 'categoria' => 'Pizza', 'preco' => 42.90],
            ['nome' => 'Pizza Portuguesa', 'categoria' => 'Pizza', 'preco' => 45.90],
            ['nome' => 'Pizza Frango com Catupiry', 'categoria' => 'Pizza', 'preco' => 47.90],
            ['nome' => 'Pizza Marguerita', 'categoria' => 'Pizza', 'preco' => 39.90],
            ['nome' => 'Picanha', 'categoria' => 'Carnes', 'preco' => 89.90],
            ['nome' => 'Picanha Premium', 'categoria' => 'Carnes', 'preco' => 119.90],
            ['nome' => 'Costela', 'categoria' => 'Carnes', 'preco' => 74.90],
            ['nome' => 'Frango Assado', 'categoria' => 'Carnes', 'preco' => 39.90],
            ['nome' => 'Hambúrguer Artesanal', 'categoria' => 'Hambúrguer', 'preco' => 29.90],
            ['nome' => 'X-Bacon', 'categoria' => 'Hambúrguer', 'preco' => 24.90],
            ['nome' => 'X-Tudo', 'categoria' => 'Hambúrguer', 'preco' => 31.90],
            ['nome' => 'Lasanha', 'categoria' => 'Massas', 'preco' => 34.90],
            ['nome' => 'Parmegiana', 'categoria' => 'Pratos', 'preco' => 49.90],
            ['nome' => 'Açaí 500ml', 'categoria' => 'Sobremesa', 'preco' => 18.90],
            ['nome' => 'Sorvete', 'categoria' => 'Sobremesa', 'preco' => 14.90],
            ['nome' => 'Coca-Cola', 'categoria' => 'Bebida', 'preco' => 8.00],
            ['nome' => 'Guaraná', 'categoria' => 'Bebida', 'preco' => 7.00],
            ['nome' => 'Suco de Laranja', 'categoria' => 'Bebida', 'preco' => 10.00],
            ['nome' => 'Água Mineral', 'categoria' => 'Bebida', 'preco' => 4.00],
        ];

        foreach ($produtos as $produto) {
            DB::table('produtos')->updateOrInsert(
                ['nome' => $produto['nome']],
                [
                    'categoria' => $produto['categoria'],
                    'preco' => $produto['preco'],
                    'ativo' => 'S',
                    'cadastrado' => $now,
                    'atualizado' => $now,
                ]
            );
        }
    }
}
