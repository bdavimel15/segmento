<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cliente')) {
            return;
        }

        Schema::table('cliente', function (Blueprint $table) {
            if (!Schema::hasColumn('cliente', 'cli_nome')) {
                $table->string('cli_nome', 255)->default('')->nullable();
            }
            if (!Schema::hasColumn('cliente', 'cli_cpf')) {
                $table->string('cli_cpf', 20)->nullable();
            }
            if (!Schema::hasColumn('cliente', 'sexo_id')) {
                $table->string('sexo_id', 20)->nullable();
            }
            if (!Schema::hasColumn('cliente', 'cli_telefone')) {
                $table->string('cli_telefone', 30)->nullable();
            }
            if (!Schema::hasColumn('cliente', 'cli_email')) {
                $table->string('cli_email', 100)->nullable();
            }
            if (!Schema::hasColumn('cliente', 'cli_cidade')) {
                $table->string('cli_cidade', 120)->nullable();
            }
            if (!Schema::hasColumn('cliente', 'cli_estado')) {
                $table->string('cli_estado', 2)->nullable();
            }
            if (!Schema::hasColumn('cliente', 'cli_bairro')) {
                $table->string('cli_bairro', 120)->nullable();
            }
            if (!Schema::hasColumn('cliente', 'cli_data_nascimento')) {
                $table->date('cli_data_nascimento')->nullable();
            }
            if (!Schema::hasColumn('cliente', 'cli_qtd_pedidos')) {
                $table->integer('cli_qtd_pedidos')->default(0);
            }
            if (!Schema::hasColumn('cliente', 'cli_newsletter')) {
                $table->string('cli_newsletter', 10)->default('S');
            }
            if (!Schema::hasColumn('cliente', 'cli_funcionario')) {
                $table->string('cli_funcionario', 10)->default('N');
            }
            if (!Schema::hasColumn('cliente', 'cli_pontos_totais')) {
                $table->integer('cli_pontos_totais')->default(0);
            }
            if (!Schema::hasColumn('cliente', 'cli_proxima_compra')) {
                $table->dateTime('cli_proxima_compra')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Não removemos colunas para evitar perda de dados reais.
    }
};
