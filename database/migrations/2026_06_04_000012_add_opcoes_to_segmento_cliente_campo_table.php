<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('segmento_cliente_campo', function (Blueprint $table) {
            if (!Schema::hasColumn('segmento_cliente_campo', 'opcoes')) {
                $table->text('opcoes')->nullable()->after('tipo_valor');
            }
        });
    }

    public function down(): void {
        Schema::table('segmento_cliente_campo', function (Blueprint $table) {
            if (Schema::hasColumn('segmento_cliente_campo', 'opcoes')) {
                $table->dropColumn('opcoes');
            }
        });
    }
};
