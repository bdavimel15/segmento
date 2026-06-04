<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('contato_grupo')) {
            Schema::create('contato_grupo', function (Blueprint $table) {
                $table->increments('contato_grupo_id');
                $table->string('cog_nome', 100)->default('');
                $table->string('cog_qtd', 50)->nullable();
                $table->text('cog_sql')->nullable();
                $table->string('cog_permitir_programar', 1)->default('N');
                $table->string('cog_campanha', 1)->default('S');
                $table->timestamp('excluido')->nullable();
                $table->timestamp('cadastrado')->nullable()->useCurrent();
                $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->date('coq_atualizacao_contador')->nullable();

                $table->index('cog_nome', 'idx_contato_grupo_nome');
                $table->index('excluido', 'idx_contato_grupo_excluido');
            });
        }

        if (!Schema::hasTable('cliente')) {
            Schema::create('cliente', function (Blueprint $table) {
                $table->increments('cliente_id');
                $table->string('cli_nome', 255)->default('');
                $table->string('cli_cpf', 20)->nullable();
                $table->char('sexo_id', 1)->nullable();
                $table->string('cli_telefone', 30)->default('');
                $table->string('cli_email', 100)->nullable();
                $table->date('cli_data_nascimento')->nullable();
                $table->integer('cli_qtd_pedidos')->default(0);
                $table->dateTime('cli_proxima_compra')->nullable();
                $table->string('cli_newsletter', 1)->default('S');
                $table->integer('cliente_origem_id')->nullable();
                $table->timestamp('excluido')->nullable();
                $table->timestamp('cadastrado')->nullable()->useCurrent();
                $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();

                $table->index('excluido', 'idx_cliente_excluido');
                $table->index('cli_telefone', 'idx_cliente_telefone');
                $table->index('cadastrado', 'idx_cliente_cadastrado');
                $table->index('cli_qtd_pedidos', 'idx_cliente_qtd_pedidos');
            });
        }

        if (!Schema::hasTable('contato')) {
            Schema::create('contato', function (Blueprint $table) {
                $table->increments('contato_id');
                $table->string('con_nome', 255)->nullable();
                $table->string('con_celular', 255)->default('');
                $table->integer('contato_origem_id')->default(1);
                $table->timestamp('excluido')->nullable();
                $table->timestamp('cadastrado')->nullable()->useCurrent();
                $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();

                $table->unique('con_celular', 'unico_contato_celular');
                $table->index('excluido', 'idx_contato_excluido');
            });
        }

        if (!Schema::hasTable('status')) {
            Schema::create('status', function (Blueprint $table) {
                $table->increments('status_id');
                $table->string('sta_nome', 50)->default('');
                $table->string('sta_confirmado', 1)->default('S');
                $table->string('sta_ativo', 1)->default('S');
                $table->timestamp('excluido')->nullable();
                $table->timestamp('cadastrado')->nullable()->useCurrent();
                $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();

                $table->index('sta_confirmado', 'idx_status_confirmado');
                $table->index('excluido', 'idx_status_excluido');
            });
        }

        if (!Schema::hasTable('pedido')) {
            Schema::create('pedido', function (Blueprint $table) {
                $table->increments('pedido_id');
                $table->string('ped_numero', 20)->nullable();
                $table->dateTime('ped_data')->nullable();
                $table->integer('cliente_id')->nullable();
                $table->integer('estabelecimento_id')->nullable();
                $table->integer('status_id')->nullable();
                $table->decimal('ped_valor_total', 10, 2)->nullable();
                $table->timestamp('excluido')->nullable();
                $table->timestamp('cadastrado')->nullable()->useCurrent();
                $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();

                $table->index('cliente_id', 'idx_pedido_cliente');
                $table->index('status_id', 'idx_pedido_status');
                $table->index('ped_data', 'idx_pedido_data');
                $table->index('excluido', 'idx_pedido_excluido');
            });
        }

        if (!Schema::hasTable('pedido_status')) {
            Schema::create('pedido_status', function (Blueprint $table) {
                $table->increments('pedido_status_id');
                $table->integer('pedido_id')->nullable();
                $table->integer('status_id')->nullable();
                $table->timestamp('excluido')->nullable();
                $table->timestamp('cadastrado')->nullable()->useCurrent();
                $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();

                $table->index('pedido_id', 'idx_pedido_status_pedido');
                $table->index('status_id', 'idx_pedido_status_status');
            });
        }

        if (!Schema::hasTable('pedido_item')) {
            Schema::create('pedido_item', function (Blueprint $table) {
                $table->increments('pedido_item_id');
                $table->integer('produto_id')->nullable();
                $table->integer('pedido_id')->nullable();
                $table->decimal('pei_quantidade', 10, 3)->nullable();
                $table->decimal('pei_valor_unitario', 10, 2)->nullable();
                $table->decimal('pei_valor_total', 10, 2)->nullable();
                $table->string('pei_descricao', 2000)->default('');
                $table->timestamp('excluido')->nullable();
                $table->timestamp('cadastrado')->nullable()->useCurrent();
                $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();

                $table->index('pedido_id', 'idx_pedido_item_pedido');
                $table->index('produto_id', 'idx_pedido_item_produto');
            });
        }

        if (!Schema::hasTable('cashback')) {
            Schema::create('cashback', function (Blueprint $table) {
                $table->increments('cashback_id');
                $table->integer('cliente_id')->nullable();
                $table->integer('pedido_id')->nullable();
                $table->decimal('cas_valor', 10, 2)->nullable();
                $table->string('cas_complemento', 255)->nullable();
                $table->timestamp('excluido')->nullable();
                $table->timestamp('cadastrado')->nullable()->useCurrent();
                $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();

                $table->index('cliente_id', 'idx_cashback_cliente');
                $table->index('excluido', 'idx_cashback_excluido');
            });
        }

        if (!Schema::hasTable('cashback_configuracao')) {
            Schema::create('cashback_configuracao', function (Blueprint $table) {
                $table->increments('cashback_configuracao_id');
                $table->integer('cac_percentual')->nullable();
                $table->string('cac_ativo', 1)->default('N');
                $table->integer('cac_tempo_expiracao')->nullable();
                $table->integer('cac_tempo_geracao')->default(2);
                $table->dateTime('excluido')->nullable();
                $table->timestamp('cadastrado')->nullable()->useCurrent();
                $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('notificacao_programada_envio')) {
            Schema::create('notificacao_programada_envio', function (Blueprint $table) {
                $table->increments('notificacao_programada_envio_id');
                $table->integer('notificacao_programada_id')->nullable();
                $table->integer('cliente_id')->nullable();
                $table->string('noe_numero', 20)->nullable();
                $table->text('noe_mensagem')->nullable();
                $table->string('noe_status', 20)->default('Pendente');
                $table->timestamp('excluido')->nullable();
                $table->timestamp('cadastrado')->nullable()->useCurrent();
                $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();

                $table->index('cliente_id', 'idx_notificacao_cliente');
                $table->index('cadastrado', 'idx_notificacao_cadastrado');
            });
        }

        if (!Schema::hasTable('estabelecimento')) {
            Schema::create('estabelecimento', function (Blueprint $table) {
                $table->increments('estabelecimento_id');
                $table->string('est_nome', 100)->default('');
                $table->string('est_ativo', 1)->default('S');
                $table->timestamp('excluido')->nullable();
                $table->timestamp('cadastrado')->nullable()->useCurrent();
                $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('segmento_cliente')) {
            Schema::create('segmento_cliente', function (Blueprint $table) {
                $table->increments('segmento_cliente_id');
                $table->string('nome', 120);
                $table->string('descricao', 255)->nullable();

                $table->string('tipo', 30)->default('dinamico');
                $table->string('origem', 30)->default('manual');

                $table->json('regra_json')->nullable();
                $table->text('resumo_humano')->nullable();

                $table->string('status_validacao', 50)->default('rascunho');
                $table->text('motivo_reprovacao')->nullable();

                $table->integer('limite')->default(25);
                $table->string('ordenacao', 50)->default('aleatoria');

                $table->string('permitir_email', 1)->default('S');
                $table->string('permitir_sms', 1)->default('S');
                $table->string('permitir_whatsapp', 1)->default('S');
                $table->string('permitir_push', 1)->default('S');

                $table->integer('ultima_previa_qtd')->nullable();
                $table->dateTime('ultima_previa_em')->nullable();

                $table->integer('validado_por')->nullable();
                $table->dateTime('validado_em')->nullable();

                $table->integer('contato_grupo_id_legado')->nullable();

                $table->timestamp('excluido')->nullable();
                $table->timestamp('cadastrado')->nullable()->useCurrent();
                $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();

                $table->index('status_validacao', 'idx_segmento_status');
                $table->index(['tipo', 'origem'], 'idx_segmento_tipo_origem');
                $table->index('contato_grupo_id_legado', 'idx_segmento_legado');
                $table->index('excluido', 'idx_segmento_excluido');
            });
        }

        if (!Schema::hasTable('segmento_cliente_execucao')) {
            Schema::create('segmento_cliente_execucao', function (Blueprint $table) {
                $table->increments('segmento_cliente_execucao_id');
                $table->integer('segmento_cliente_id');
                $table->string('canal', 30)->default('preview');
                $table->json('regra_json_snapshot');
                $table->longText('sql_gerada_snapshot')->nullable();
                $table->integer('total_encontrado')->default(0);
                $table->integer('total_processado')->default(0);
                $table->integer('total_enviado')->default(0);
                $table->string('status', 30)->default('pendente');
                $table->text('erro')->nullable();
                $table->integer('executado_por')->nullable();
                $table->dateTime('executado_em')->nullable();
                $table->timestamp('cadastrado')->nullable()->useCurrent();

                $table->index('segmento_cliente_id', 'idx_execucao_segmento');
                $table->index('status', 'idx_execucao_status');
                $table->index('canal', 'idx_execucao_canal');
            });
        }

        if (!Schema::hasTable('segmento_cliente_campo')) {
            Schema::create('segmento_cliente_campo', function (Blueprint $table) {
                $table->increments('segmento_cliente_campo_id');
                $table->string('chave', 80);
                $table->string('label', 120);
                $table->string('descricao', 255)->nullable();
                $table->string('categoria', 50);
                $table->string('tipo_valor', 30);
                $table->string('origem_tabela', 120)->nullable();
                $table->string('origem_coluna', 120)->nullable();
                $table->text('expressao_sql')->nullable();
                $table->json('operadores_json');
                $table->string('ativo', 1)->default('S');
                $table->integer('ordem')->default(0);
                $table->timestamp('cadastrado')->nullable()->useCurrent();
                $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();

                $table->unique('chave', 'uk_segmento_campo_chave');
                $table->index('categoria', 'idx_campo_categoria');
                $table->index('ativo', 'idx_campo_ativo');
            });
        }

        if (!Schema::hasTable('segmento_cliente_validacao')) {
            Schema::create('segmento_cliente_validacao', function (Blueprint $table) {
                $table->increments('segmento_cliente_validacao_id');
                $table->integer('segmento_cliente_id');
                $table->string('status_anterior', 50)->nullable();
                $table->string('status_novo', 50);
                $table->json('regra_json_snapshot')->nullable();
                $table->text('resumo_humano_snapshot')->nullable();
                $table->text('observacao')->nullable();
                $table->integer('validado_por')->nullable();
                $table->timestamp('cadastrado')->nullable()->useCurrent();

                $table->index('segmento_cliente_id', 'idx_validacao_segmento');
                $table->index('status_novo', 'idx_validacao_status');
            });
        }

        if (!Schema::hasTable('segmento_cliente_preset')) {
            Schema::create('segmento_cliente_preset', function (Blueprint $table) {
                $table->increments('segmento_cliente_preset_id');
                $table->string('nome', 120);
                $table->string('descricao', 255)->nullable();
                $table->string('categoria', 80)->nullable();
                $table->json('regra_json');
                $table->string('ativo', 1)->default('S');
                $table->integer('ordem')->default(0);
                $table->timestamp('cadastrado')->nullable()->useCurrent();
                $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();

                $table->unique('nome', 'uk_preset_nome');
                $table->index('categoria', 'idx_preset_categoria');
                $table->index('ativo', 'idx_preset_ativo');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('segmento_cliente_preset');
        Schema::dropIfExists('segmento_cliente_validacao');
        Schema::dropIfExists('segmento_cliente_campo');
        Schema::dropIfExists('segmento_cliente_execucao');
        Schema::dropIfExists('segmento_cliente');
        Schema::dropIfExists('estabelecimento');
        Schema::dropIfExists('notificacao_programada_envio');
        Schema::dropIfExists('cashback_configuracao');
        Schema::dropIfExists('cashback');
        Schema::dropIfExists('pedido_item');
        Schema::dropIfExists('pedido_status');
        Schema::dropIfExists('pedido');
        Schema::dropIfExists('status');
        Schema::dropIfExists('contato');
        Schema::dropIfExists('cliente');
        Schema::dropIfExists('contato_grupo');
    }
};
