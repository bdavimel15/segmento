<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('segmento_cliente_execucao')) {
            Schema::table('segmento_cliente_execucao', function (Blueprint $table) {
                if (!Schema::hasColumn('segmento_cliente_execucao', 'segmento_cliente_id')) $table->integer('segmento_cliente_id');
                if (!Schema::hasColumn('segmento_cliente_execucao', 'canal')) $table->enum('canal', ['email','sms','whatsapp','push','preview','exportacao'])->default('preview');
                if (!Schema::hasColumn('segmento_cliente_execucao', 'regra_json_snapshot')) $table->json('regra_json_snapshot')->nullable();
                if (!Schema::hasColumn('segmento_cliente_execucao', 'sql_gerada_snapshot')) $table->longText('sql_gerada_snapshot')->nullable();
                if (!Schema::hasColumn('segmento_cliente_execucao', 'total_encontrado')) $table->integer('total_encontrado')->default(0);
                if (!Schema::hasColumn('segmento_cliente_execucao', 'total_processado')) $table->integer('total_processado')->default(0);
                if (!Schema::hasColumn('segmento_cliente_execucao', 'total_enviado')) $table->integer('total_enviado')->default(0);
                if (!Schema::hasColumn('segmento_cliente_execucao', 'status')) $table->enum('status', ['pendente','executando','concluida','erro','cancelada'])->default('pendente');
                if (!Schema::hasColumn('segmento_cliente_execucao', 'erro')) $table->text('erro')->nullable();
                if (!Schema::hasColumn('segmento_cliente_execucao', 'executado_por')) $table->integer('executado_por')->nullable();
                if (!Schema::hasColumn('segmento_cliente_execucao', 'executado_em')) $table->dateTime('executado_em')->nullable();
                if (!Schema::hasColumn('segmento_cliente_execucao', 'cadastrado')) $table->timestamp('cadastrado')->nullable()->useCurrent();
            });
            return;
        }

        Schema::create('segmento_cliente_execucao', function (Blueprint $table) {
            $table->increments('segmento_cliente_execucao_id');
            $table->integer('segmento_cliente_id');
            $table->enum('canal', ['email','sms','whatsapp','push','preview','exportacao'])->default('preview');
            $table->json('regra_json_snapshot');
            $table->longText('sql_gerada_snapshot')->nullable();
            $table->integer('total_encontrado')->default(0);
            $table->integer('total_processado')->default(0);
            $table->integer('total_enviado')->default(0);
            $table->enum('status', ['pendente','executando','concluida','erro','cancelada'])->default('pendente');
            $table->text('erro')->nullable();
            $table->integer('executado_por')->nullable();
            $table->dateTime('executado_em')->nullable();
            $table->timestamp('cadastrado')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('segmento_cliente_execucao');
    }
};
