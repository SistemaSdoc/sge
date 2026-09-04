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
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('trabalho_pap', function (Blueprint $table): void {
            $table->dropForeign(['aprovado_por_id']);
        });

        Schema::table('trabalho_pap', function (Blueprint $table): void {
            $table->uuid('aprovado_por_id')->nullable()->change();
            $table->foreign('aprovado_por_id')->references('id')->on('users')->nullOnDelete();
            $table->uuid('aprovado_por_externo_id')->nullable()->after('aprovado_por_id');
            $table->uuid('aprovado_por_externo_tenant_id')->nullable()->after('aprovado_por_externo_id');
            $table->string('aprovado_por_nome')->nullable()->after('aprovado_por_externo_tenant_id');
        });

        Schema::table('trabalho_pap_feedbacks', function (Blueprint $table): void {
            $table->dropForeign(['utilizador_id']);
        });

        Schema::table('trabalho_pap_feedbacks', function (Blueprint $table): void {
            $table->uuid('utilizador_id')->nullable()->change();
            $table->foreign('utilizador_id')->references('id')->on('users')->nullOnDelete();
            $table->uuid('utilizador_externo_id')->nullable()->after('utilizador_id');
            $table->uuid('utilizador_externo_tenant_id')->nullable()->after('utilizador_externo_id');
            $table->string('utilizador_nome')->nullable()->after('utilizador_externo_tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('trabalho_pap_feedbacks', function (Blueprint $table): void {
            $table->dropForeign(['utilizador_id']);
            $table->dropColumn([
                'utilizador_externo_id',
                'utilizador_externo_tenant_id',
                'utilizador_nome',
            ]);
            $table->uuid('utilizador_id')->nullable(false)->change();
            $table->foreign('utilizador_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('trabalho_pap', function (Blueprint $table): void {
            $table->dropColumn([
                'aprovado_por_externo_id',
                'aprovado_por_externo_tenant_id',
                'aprovado_por_nome',
            ]);
            $table->dropForeign(['aprovado_por_id']);
            $table->uuid('aprovado_por_id')->nullable(false)->change();
            $table->foreign('aprovado_por_id')->references('id')->on('users')->restrictOnDelete();
        });
    }
};
