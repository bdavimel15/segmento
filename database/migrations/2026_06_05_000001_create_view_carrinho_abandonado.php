<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cliente')) {
            return;
        }

        try {
            DB::select('SELECT cliente_id FROM view_carrinho_abandonado LIMIT 1');
            return;
        } catch (\Throwable) {
            // view ainda não existe
        }

        DB::statement('CREATE VIEW view_carrinho_abandonado AS SELECT cliente_id FROM cliente WHERE 1 = 0');
    }

    public function down(): void
    {
        if (Schema::hasTable('view_carrinho_abandonado')) {
            DB::statement('DROP VIEW view_carrinho_abandonado');
        }
    }
};
