<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MockClienteSeeder::class,
            SegmentoClienteCampoSeeder::class,
            ProdutoRestauranteSeeder::class,
            PedidoProdutoClienteSeeder::class,
            ProdutoCampoFiltroSeeder::class,
            SegmentoPresetSeeder::class,
        ]);
    }
}
