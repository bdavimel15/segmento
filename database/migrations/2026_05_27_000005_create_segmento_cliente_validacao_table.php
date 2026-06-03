<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('segmento_cliente_validacao')) {
            Schema::table('segmento_cliente_validacao', function (Blueprint $table) {
                if (!Schema::hasColumn('segmento_cliente_validacao', 'segmento_cliente_id')) $table->unsignedBigInteger('segmento_cliente_id')->nullable();
                if (!Schema::hasColumn('segmento_cliente_validacao', 'status_anterior')) $table->string('status_anterior', 50)->nullable();
                if (!Schema::hasColumn('segmento_cliente_validacao', 'status_novo')) $table->string('status_novo', 50)->nullable();
                if (!Schema::hasColumn('segmento_cliente_validacao', 'regra_json_snapshot')) $table->json('regra_json_snapshot')->nullable();
                if (!Schema::hasColumn('segmento_cliente_validacao', 'resumo_humano_snapshot')) $table->text('resumo_humano_snapshot')->nullable();
                if (!Schema::hasColumn('segmento_cliente_validacao', 'observacao')) $table->text('observacao')->nullable();
                if (!Schema::hasColumn('segmento_cliente_validacao', 'validado_por')) $table->unsignedBigInteger('validado_por')->nullable();
                if (!Schema::hasColumn('segmento_cliente_validacao', 'cadastrado')) $table->timestamp('cadastrado')->nullable()->useCurrent();
            });
            return;
        }

        Schema::create('segmento_cliente_validacao', function (Blueprint $table) {
            $table->id('segmento_cliente_validacao_id');
            $table->unsignedBigInteger('segmento_cliente_id');
            $table->string('status_anterior', 50)->nullable();
            $table->string('status_novo', 50);
            $table->json('regra_json_snapshot')->nullable();
            $table->text('resumo_humano_snapshot')->nullable();
            $table->text('observacao')->nullable();
            $table->unsignedBigInteger('validado_por')->nullable();
            $table->timestamp('cadastrado')->nullable()->useCurrent();
            $table->index('segmento_cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('segmento_cliente_validacao');
    }
};
