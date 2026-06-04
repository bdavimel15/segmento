<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('segmento_cliente_campo')) {
            Schema::table('segmento_cliente_campo', function (Blueprint $table) {
                if (! Schema::hasColumn('segmento_cliente_campo', 'opcoes_json')) {
                    $table->text('opcoes_json')->nullable()->after('operadores_json');
                }
            });
        }

        if (Schema::hasTable('pedido')) {
            Schema::table('pedido', function (Blueprint $table) {
                if (! Schema::hasColumn('pedido', 'canal_pedido')) {
                    $table->string('canal_pedido', 60)->nullable()->after('status_id');
                }

                if (! Schema::hasColumn('pedido', 'forma_pagamento')) {
                    $table->string('forma_pagamento', 60)->nullable()->after('canal_pedido');
                }
            });
        }
    }

    public function down(): void
    {
        // Não remove colunas para evitar perda de dados em produção/demo.
    }
};
