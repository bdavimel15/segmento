<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('segmento_cliente_campo')) {
            Schema::table('segmento_cliente_campo', function (Blueprint $table) {
                if (!Schema::hasColumn('segmento_cliente_campo', 'chave')) $table->string('chave', 80)->nullable();
                if (!Schema::hasColumn('segmento_cliente_campo', 'label')) $table->string('label', 120)->nullable();
                if (!Schema::hasColumn('segmento_cliente_campo', 'descricao')) $table->string('descricao', 255)->nullable();
                if (!Schema::hasColumn('segmento_cliente_campo', 'categoria')) $table->string('categoria', 80)->nullable();
                if (!Schema::hasColumn('segmento_cliente_campo', 'tipo_valor')) $table->string('tipo_valor', 40)->nullable();
                if (!Schema::hasColumn('segmento_cliente_campo', 'origem_tabela')) $table->string('origem_tabela', 120)->nullable();
                if (!Schema::hasColumn('segmento_cliente_campo', 'origem_coluna')) $table->string('origem_coluna', 120)->nullable();
                if (!Schema::hasColumn('segmento_cliente_campo', 'expressao_sql')) $table->text('expressao_sql')->nullable();
                if (!Schema::hasColumn('segmento_cliente_campo', 'operadores_json')) $table->json('operadores_json')->nullable();
                if (!Schema::hasColumn('segmento_cliente_campo', 'ativo')) $table->enum('ativo', ['S','N'])->default('S');
                if (!Schema::hasColumn('segmento_cliente_campo', 'ordem')) $table->integer('ordem')->default(0);
                if (!Schema::hasColumn('segmento_cliente_campo', 'cadastrado')) $table->timestamp('cadastrado')->nullable()->useCurrent();
                if (!Schema::hasColumn('segmento_cliente_campo', 'atualizado')) $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();
            });
            return;
        }

        Schema::create('segmento_cliente_campo', function (Blueprint $table) {
            $table->increments('segmento_cliente_campo_id');
            $table->string('chave', 80)->unique();
            $table->string('label', 120);
            $table->string('descricao', 255)->nullable();
            $table->enum('categoria', ['cliente','contato','pedido','produto','cashback','carrinho','notificacao','endereco','sistema']);
            $table->enum('tipo_valor', ['string','number','date','datetime','boolean','money','select']);
            $table->string('origem_tabela', 120)->nullable();
            $table->string('origem_coluna', 120)->nullable();
            $table->text('expressao_sql')->nullable();
            $table->json('operadores_json');
            $table->enum('ativo', ['S','N'])->default('S');
            $table->integer('ordem')->default(0);
            $table->timestamp('cadastrado')->nullable()->useCurrent();
            $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('segmento_cliente_campo');
    }
};
