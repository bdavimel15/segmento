<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('produto')) {
            Schema::create('produto', function (Blueprint $table) {
                $table->increments('produto_id');
                $table->string('pro_nome', 255);
                $table->string('pro_sku', 80)->nullable();
                $table->decimal('pro_preco', 10, 2)->default(0);
                $table->timestamp('excluido')->nullable();
                $table->timestamp('cadastrado')->nullable()->useCurrent();
                $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();

                $table->index('pro_nome');
                $table->index('excluido');
            });
        } else {
            Schema::table('produto', function (Blueprint $table) {
                if (!Schema::hasColumn('produto', 'pro_nome')) {
                    $table->string('pro_nome', 255)->nullable()->after('produto_id');
                }
                if (!Schema::hasColumn('produto', 'pro_sku')) {
                    $table->string('pro_sku', 80)->nullable()->after('pro_nome');
                }
                if (!Schema::hasColumn('produto', 'pro_preco')) {
                    $table->decimal('pro_preco', 10, 2)->default(0)->after('pro_sku');
                }
                if (!Schema::hasColumn('produto', 'excluido')) {
                    $table->timestamp('excluido')->nullable();
                }
                if (!Schema::hasColumn('produto', 'cadastrado')) {
                    $table->timestamp('cadastrado')->nullable()->useCurrent();
                }
                if (!Schema::hasColumn('produto', 'atualizado')) {
                    $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();
                }
            });
        }

        if (!Schema::hasTable('pedido_item')) {
            Schema::create('pedido_item', function (Blueprint $table) {
                $table->increments('pedido_item_id');
                $table->integer('pedido_id')->nullable();
                $table->integer('produto_id')->nullable();
                $table->integer('pei_quantidade')->default(1);
                $table->decimal('pei_valor_unitario', 10, 2)->default(0);
                $table->decimal('pei_valor_total', 10, 2)->default(0);
                $table->timestamp('excluido')->nullable();
                $table->timestamp('cadastrado')->nullable()->useCurrent();
                $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();

                $table->index('pedido_id');
                $table->index('produto_id');
                $table->index('excluido');
            });
        } else {
            Schema::table('pedido_item', function (Blueprint $table) {
                if (!Schema::hasColumn('pedido_item', 'pedido_item_id')) {
                    // Não tenta alterar chave primária de tabela real existente.
                }
                if (!Schema::hasColumn('pedido_item', 'pedido_id')) {
                    $table->integer('pedido_id')->nullable();
                }
                if (!Schema::hasColumn('pedido_item', 'produto_id')) {
                    $table->integer('produto_id')->nullable();
                }
                if (!Schema::hasColumn('pedido_item', 'pei_quantidade')) {
                    $table->integer('pei_quantidade')->default(1);
                }
                if (!Schema::hasColumn('pedido_item', 'pei_valor_unitario')) {
                    $table->decimal('pei_valor_unitario', 10, 2)->default(0);
                }
                if (!Schema::hasColumn('pedido_item', 'pei_valor_total')) {
                    $table->decimal('pei_valor_total', 10, 2)->default(0);
                }
                if (!Schema::hasColumn('pedido_item', 'excluido')) {
                    $table->timestamp('excluido')->nullable();
                }
                if (!Schema::hasColumn('pedido_item', 'cadastrado')) {
                    $table->timestamp('cadastrado')->nullable()->useCurrent();
                }
                if (!Schema::hasColumn('pedido_item', 'atualizado')) {
                    $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();
                }
            });
        }
    }

    public function down(): void
    {
        // Não removemos tabelas porque podem ser compartilhadas com dados reais.
    }
};
