<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createOrFixCliente();
        $this->createOrFixStatus();
        $this->createOrFixPedido();
        $this->createOrFixCashback();
    }

    private function createOrFixCliente(): void
    {
        if (!Schema::hasTable('cliente')) {
            Schema::create('cliente', function (Blueprint $table) {
                $table->increments('cliente_id');
                $table->string('cli_nome', 255)->default('');
                $table->string('cli_cpf', 20)->nullable();
                $table->char('sexo_id', 1)->nullable();
                $table->string('cli_telefone', 30)->default('');
                $table->string('cli_email', 100)->nullable();
                $table->string('cli_cidade', 120)->nullable();
                $table->string('cli_estado', 2)->nullable();
                $table->string('cli_bairro', 120)->nullable();
                $table->date('cli_data_nascimento')->nullable();
                $table->integer('cli_qtd_pedidos')->default(0);
                $table->dateTime('cli_proxima_compra')->nullable();
                $table->enum('cli_newsletter', ['S', 'N'])->default('S');
                $table->enum('cli_funcionario', ['S', 'N'])->default('N');
                $table->integer('cli_pontos_totais')->default(0);
                $table->integer('cliente_origem_id')->nullable();
                $table->timestamp('excluido')->nullable();
                $table->timestamp('cadastrado')->nullable()->useCurrent();
                $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();
            });
        }

        $this->ensureColumn('cliente', 'cli_cpf', fn (Blueprint $table) => $table->string('cli_cpf', 20)->nullable()->after('cli_nome'));
        $this->ensureColumn('cliente', 'cli_cidade', fn (Blueprint $table) => $table->string('cli_cidade', 120)->nullable()->after('cli_email'));
        $this->ensureColumn('cliente', 'cli_estado', fn (Blueprint $table) => $table->string('cli_estado', 2)->nullable()->after('cli_cidade'));
        $this->ensureColumn('cliente', 'cli_bairro', fn (Blueprint $table) => $table->string('cli_bairro', 120)->nullable()->after('cli_estado'));
        $this->ensureColumn('cliente', 'cli_funcionario', fn (Blueprint $table) => $table->enum('cli_funcionario', ['S', 'N'])->default('N')->after('cli_newsletter'));
        $this->ensureColumn('cliente', 'cli_pontos_totais', fn (Blueprint $table) => $table->integer('cli_pontos_totais')->default(0)->after('cli_funcionario'));
        $this->ensureColumn('cliente', 'cadastrado', fn (Blueprint $table) => $table->timestamp('cadastrado')->nullable()->useCurrent()->after('excluido'));
        $this->ensureColumn('cliente', 'atualizado', fn (Blueprint $table) => $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate()->after('cadastrado'));

        $this->addIndexIfExists('cliente', 'excluido');
        $this->addIndexIfExists('cliente', 'cadastrado');
        $this->addIndexIfExists('cliente', 'cli_qtd_pedidos');
        $this->addIndexIfExists('cliente', 'sexo_id');
        $this->addIndexIfExists('cliente', 'cli_cidade');
        $this->addIndexIfExists('cliente', 'cli_estado');
    }

    private function createOrFixStatus(): void
    {
        if (!Schema::hasTable('status')) {
            Schema::create('status', function (Blueprint $table) {
                $table->increments('status_id');
                $table->string('sta_nome', 50)->default('');
                $table->enum('sta_confirmado', ['S', 'N'])->default('S');
                $table->enum('sta_ativo', ['S', 'N'])->default('S');
                $table->timestamp('excluido')->nullable();
                $table->timestamp('cadastrado')->nullable()->useCurrent();
                $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate();
            });
        }

        $this->ensureColumn('status', 'cadastrado', fn (Blueprint $table) => $table->timestamp('cadastrado')->nullable()->useCurrent()->after('excluido'));
        $this->ensureColumn('status', 'atualizado', fn (Blueprint $table) => $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate()->after('cadastrado'));

        $this->addIndexIfExists('status', 'sta_confirmado');
        $this->addIndexIfExists('status', 'excluido');
    }

    private function createOrFixPedido(): void
    {
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
            });
        }

        $this->ensureColumn('pedido', 'cadastrado', fn (Blueprint $table) => $table->timestamp('cadastrado')->nullable()->useCurrent()->after('excluido'));
        $this->ensureColumn('pedido', 'atualizado', fn (Blueprint $table) => $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate()->after('cadastrado'));

        $this->addIndexIfExists('pedido', 'cliente_id');
        $this->addIndexIfExists('pedido', 'status_id');
        $this->addIndexIfExists('pedido', 'ped_data');
        $this->addIndexIfExists('pedido', 'excluido');
    }

    private function createOrFixCashback(): void
    {
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
            });
        }

        $this->ensureColumn('cashback', 'cadastrado', fn (Blueprint $table) => $table->timestamp('cadastrado')->nullable()->useCurrent()->after('excluido'));
        $this->ensureColumn('cashback', 'atualizado', fn (Blueprint $table) => $table->timestamp('atualizado')->nullable()->useCurrent()->useCurrentOnUpdate()->after('cadastrado'));

        $this->addIndexIfExists('cashback', 'cliente_id');
        $this->addIndexIfExists('cashback', 'excluido');
    }

    private function ensureColumn(string $tableName, string $columnName, callable $definition): void
    {
        if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, $columnName)) {
            Schema::table($tableName, function (Blueprint $table) use ($definition) {
                $definition($table);
            });
        }
    }

    private function addIndexIfExists(string $tableName, string $columnName): void
    {
        if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        $indexName = $tableName . '_' . $columnName . '_index';

        if ($this->indexExists($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columnName, $indexName) {
            $table->index($columnName, $indexName);
        });
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        $tableName = str_replace('`', '', $tableName);
        $indexName = str_replace('`', '', $indexName);

        try {
            if (DB::getDriverName() === 'sqlite') {
                $indexes = DB::select("PRAGMA index_list('" . str_replace("'", "''", $tableName) . "')");

                foreach ($indexes as $index) {
                    if (($index->name ?? '') === $indexName) {
                        return true;
                    }
                }

                return false;
            }

            return count(DB::select("SHOW INDEX FROM `{$tableName}` WHERE Key_name = ?", [$indexName])) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function down(): void
    {
        // Não removemos tabelas base automaticamente para evitar apagar dados reais.
    }
};
