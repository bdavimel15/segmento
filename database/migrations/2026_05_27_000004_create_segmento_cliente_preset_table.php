<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('segmento_cliente_preset')) {
            Schema::table('segmento_cliente_preset', function (Blueprint $table) {
                if (!Schema::hasColumn('segmento_cliente_preset', 'nome')) $table->string('nome', 120)->nullable();
                if (!Schema::hasColumn('segmento_cliente_preset', 'descricao')) $table->string('descricao', 255)->nullable();
                if (!Schema::hasColumn('segmento_cliente_preset', 'categoria')) $table->string('categoria', 80)->nullable();
                if (!Schema::hasColumn('segmento_cliente_preset', 'regra_json')) $table->json('regra_json')->nullable();
                if (!Schema::hasColumn('segmento_cliente_preset', 'ativo')) $table->enum('ativo', ['S','N'])->default('S');
                if (!Schema::hasColumn('segmento_cliente_preset', 'ordem')) $table->integer('ordem')->default(0);
                if (!Schema::hasColumn('segmento_cliente_preset', 'cadastrado')) $table->timestamp('cadastrado')->nullable()->useCurrent();
                if (!Schema::hasColumn('segmento_cliente_preset', 'atualizado')) $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();
            });
            return;
        }

        Schema::create('segmento_cliente_preset', function (Blueprint $table) {
            $table->increments('segmento_cliente_preset_id');
            $table->string('nome', 120);
            $table->string('descricao', 255)->nullable();
            $table->string('categoria', 80)->nullable();
            $table->json('regra_json');
            $table->enum('ativo', ['S','N'])->default('S');
            $table->integer('ordem')->default(0);
            $table->timestamp('cadastrado')->nullable()->useCurrent();
            $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('segmento_cliente_preset');
    }
};
