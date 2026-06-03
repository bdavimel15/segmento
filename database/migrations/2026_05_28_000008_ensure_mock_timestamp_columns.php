<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['cliente', 'pedido', 'status', 'cashback'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'cadastrado')) {
                    $table->timestamp('cadastrado')->nullable()->useCurrent();
                }
                if (!Schema::hasColumn($tableName, 'atualizado')) {
                    $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();
                }
            });
        }
    }

    public function down(): void
    {
        // Não removemos colunas para evitar perda de dados.
    }
};
