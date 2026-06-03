<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('segmento_cliente')) {
            Schema::table('segmento_cliente', function (Blueprint $table) {
                if (!Schema::hasColumn('segmento_cliente', 'descricao')) $table->string('descricao', 255)->nullable();
                if (!Schema::hasColumn('segmento_cliente', 'tipo')) $table->enum('tipo', ['dinamico','manual','importado','sql_legado'])->default('dinamico');
                if (!Schema::hasColumn('segmento_cliente', 'origem')) $table->enum('origem', ['manual','ia','preset','legado'])->default('manual');
                if (!Schema::hasColumn('segmento_cliente', 'regra_json')) $table->json('regra_json')->nullable();
                if (!Schema::hasColumn('segmento_cliente', 'resumo_humano')) $table->text('resumo_humano')->nullable();
                if (!Schema::hasColumn('segmento_cliente', 'status_validacao')) $table->enum('status_validacao', ['rascunho','pendente_validacao','validada','reprovada','inativa','erro'])->default('rascunho');
                if (!Schema::hasColumn('segmento_cliente', 'motivo_reprovacao')) $table->text('motivo_reprovacao')->nullable();
                if (!Schema::hasColumn('segmento_cliente', 'limite')) $table->integer('limite')->default(25);
                if (!Schema::hasColumn('segmento_cliente', 'ordenacao')) $table->enum('ordenacao', ['aleatoria','mais_recentes','mais_antigos','maior_valor','menor_valor','ultima_compra_desc','ultima_compra_asc'])->default('aleatoria');
                if (!Schema::hasColumn('segmento_cliente', 'permitir_email')) $table->enum('permitir_email', ['S','N'])->default('S');
                if (!Schema::hasColumn('segmento_cliente', 'permitir_sms')) $table->enum('permitir_sms', ['S','N'])->default('S');
                if (!Schema::hasColumn('segmento_cliente', 'permitir_whatsapp')) $table->enum('permitir_whatsapp', ['S','N'])->default('S');
                if (!Schema::hasColumn('segmento_cliente', 'permitir_push')) $table->enum('permitir_push', ['S','N'])->default('S');
                if (!Schema::hasColumn('segmento_cliente', 'ultima_previa_qtd')) $table->integer('ultima_previa_qtd')->nullable();
                if (!Schema::hasColumn('segmento_cliente', 'ultima_previa_em')) $table->dateTime('ultima_previa_em')->nullable();
                if (!Schema::hasColumn('segmento_cliente', 'validado_por')) $table->integer('validado_por')->nullable();
                if (!Schema::hasColumn('segmento_cliente', 'validado_em')) $table->dateTime('validado_em')->nullable();
                if (!Schema::hasColumn('segmento_cliente', 'contato_grupo_id_legado')) $table->integer('contato_grupo_id_legado')->nullable();
                if (!Schema::hasColumn('segmento_cliente', 'excluido')) $table->timestamp('excluido')->nullable();
                if (!Schema::hasColumn('segmento_cliente', 'cadastrado')) $table->timestamp('cadastrado')->nullable()->useCurrent();
                if (!Schema::hasColumn('segmento_cliente', 'atualizado')) $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();
            });
            return;
        }

        Schema::create('segmento_cliente', function (Blueprint $table) {
            $table->increments('segmento_cliente_id');
            $table->string('nome', 120);
            $table->string('descricao', 255)->nullable();
            $table->enum('tipo', ['dinamico','manual','importado','sql_legado'])->default('dinamico');
            $table->enum('origem', ['manual','ia','preset','legado'])->default('manual');
            $table->json('regra_json')->nullable();
            $table->text('resumo_humano')->nullable();
            $table->enum('status_validacao', ['rascunho','pendente_validacao','validada','reprovada','inativa','erro'])->default('rascunho');
            $table->text('motivo_reprovacao')->nullable();
            $table->integer('limite')->default(25);
            $table->enum('ordenacao', ['aleatoria','mais_recentes','mais_antigos','maior_valor','menor_valor','ultima_compra_desc','ultima_compra_asc'])->default('aleatoria');
            $table->enum('permitir_email', ['S','N'])->default('S');
            $table->enum('permitir_sms', ['S','N'])->default('S');
            $table->enum('permitir_whatsapp', ['S','N'])->default('S');
            $table->enum('permitir_push', ['S','N'])->default('S');
            $table->integer('ultima_previa_qtd')->nullable();
            $table->dateTime('ultima_previa_em')->nullable();
            $table->integer('validado_por')->nullable();
            $table->dateTime('validado_em')->nullable();
            $table->integer('contato_grupo_id_legado')->nullable();
            $table->timestamp('excluido')->nullable();
            $table->timestamp('cadastrado')->nullable()->useCurrent();
            $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('segmento_cliente');
    }
};
