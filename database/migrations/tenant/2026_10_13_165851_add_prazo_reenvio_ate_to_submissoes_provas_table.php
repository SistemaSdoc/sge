<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabela onde a coluna será adicionada.
     * Confirme com: php artisan tinker --execute="echo (new App\Models\Tenant\SubmissaoProva)->getTable();"
     */
    private string $tabela = 'submissoes_provas';

    public function up(): void
    {
        Schema::table($this->tabela, function (Blueprint $table) {
            $table->timestamp('prazo_reenvio_ate')
                ->nullable()
                ->after('parecer_diretor')
                ->comment('Janela pessoal de reenvio concedida após rejeição');
        });
    }

    public function down(): void
    {
        Schema::table($this->tabela, function (Blueprint $table) {
            $table->dropColumn('prazo_reenvio_ate');
        });
    }
};