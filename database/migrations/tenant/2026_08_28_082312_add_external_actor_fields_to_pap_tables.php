<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('grupo_pap', function (Blueprint $table) {
            $table->uuid('professor_tutor_id')->nullable()->change();
            $table->uuid('professor_tutor_externo_id')->nullable()->after('professor_tutor_id');
            $table->string('professor_tutor_externo_tenant_id')->nullable()->after('professor_tutor_externo_id');
            $table->uuid('aprovado_por_externo_id')->nullable()->after('aprovado_por_id');
            $table->string('aprovado_por_externo_tenant_id')->nullable()->after('aprovado_por_externo_id');
            $table->string('aprovado_por_nome')->nullable()->after('aprovado_por_externo_tenant_id');

            $table->dropForeign(['aprovado_por_id']);
            $table->uuid('aprovado_por_id')->nullable()->change();
        });

        Schema::table('banca_juri_pap', function (Blueprint $table) {
            $table->dropForeign(['professor_id']);
            $table->uuid('professor_id')->nullable()->change();
            $table->uuid('professor_externo_id')->nullable()->after('professor_id');
            $table->string('professor_externo_tenant_id')->nullable()->after('professor_externo_id');
            $table->foreign('professor_id')->references('id')->on('professores')->nullOnDelete();
        });

        Schema::table('historico_aprovacao_pap', function (Blueprint $table) {
            $table->uuid('utilizador_externo_id')->nullable()->after('utilizador_id');
            $table->string('utilizador_externo_tenant_id')->nullable()->after('utilizador_externo_id');
            $table->string('utilizador_nome')->nullable()->after('utilizador_externo_tenant_id');
            $table->dropForeign(['utilizador_id']);
            $table->uuid('utilizador_id')->nullable()->change();
            $table->foreign('utilizador_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grupo_pap', function (Blueprint $table) {
            $table->uuid('professor_tutor_id')->nullable(false)->change();
            $table->foreign('aprovado_por_id')->references('id')->on('users')->nullOnDelete();
            $table->dropColumn([
                'professor_tutor_externo_id',
                'professor_tutor_externo_tenant_id',
                'aprovado_por_externo_id',
                'aprovado_por_externo_tenant_id',
                'aprovado_por_nome',
            ]);
        });

        Schema::table('banca_juri_pap', function (Blueprint $table) {
            $table->dropForeign(['professor_id']);
            $table->uuid('professor_id')->nullable(false)->change();
            $table->foreign('professor_id')->references('id')->on('professores');
            $table->dropColumn(['professor_externo_id', 'professor_externo_tenant_id']);
        });

        Schema::table('historico_aprovacao_pap', function (Blueprint $table) {
            $table->dropForeign(['utilizador_id']);
            $table->uuid('utilizador_id')->nullable(false)->change();
            $table->foreign('utilizador_id')->references('id')->on('users')->restrictOnDelete();
            $table->dropColumn([
                'utilizador_externo_id',
                'utilizador_externo_tenant_id',
                'utilizador_nome',
            ]);
        });
    }
};
