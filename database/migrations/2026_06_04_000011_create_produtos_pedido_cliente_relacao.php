<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('produtos')) {
            Schema::create('produtos', function (Blueprint $table) {
                $table->increments('produto_id');
                $table->string('nome', 150);
                $table->string('categoria', 80)->nullable();
                $table->decimal('preco', 10, 2)->default(0);
                $table->enum('ativo', ['S', 'N'])->default('S');
                $table->timestamp('cadastrado')->nullable();
                $table->timestamp('atualizado')->nullable();

                $table->index('nome');
                $table->index('categoria');
                $table->index('ativo');
            });
        }

        if (! Schema::hasTable('pedido_produto_cliente')) {
            Schema::create('pedido_produto_cliente', function (Blueprint $table) {
                $table->increments('pedido_produto_cliente_id');
                $table->unsignedInteger('cliente_id');
                $table->unsignedInteger('pedido_id');
                $table->unsignedInteger('produto_id');
                $table->integer('quantidade')->default(1);
                $table->decimal('valor_unitario', 10, 2)->default(0);
                $table->decimal('valor_total', 10, 2)->default(0);
                $table->timestamp('cadastrado')->nullable();
                $table->timestamp('atualizado')->nullable();

                $table->index('cliente_id');
                $table->index('pedido_id');
                $table->index('produto_id');
                $table->index(['cliente_id', 'produto_id']);
                $table->index(['pedido_id', 'produto_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_produto_cliente');
        Schema::dropIfExists('produtos');
    }
};
