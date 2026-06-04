<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MockClienteSeeder::class,
            ProdutoRestauranteSeeder::class,
            PedidoProdutoClienteSeeder::class,
            SegmentoClienteCampoSeeder::class,
            ProdutoCampoFiltroSeeder::class,
            SegmentoPresetSeeder::class,
        ]);
    }
}
