<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected function foreignKeyExists(string $table, string $column): bool
    {
        $result = DB::selectOne(
            'SELECT COUNT(*) AS total
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL',
            [
                config('database.connections.'.config('database.default').'.database'),
                $table,
                $column,
            ]
        );

        return (int) ($result->total ?? 0) > 0;
    }

    public function up(): void
    {
        if (Schema::hasTable('prazos_provas')) {
            Schema::table('prazos_provas', function (Blueprint $table): void {
                if (! $this->foreignKeyExists('prazos_provas', 'classe_id')) {
                    $table->foreign('classe_id', 'prazos_provas_classe_id_foreign')
                        ->references('id')
                        ->on('classes')
                        ->nullOnDelete();
                }

                if (! $this->foreignKeyExists('prazos_provas', 'criado_por')) {
                    $table->foreign('criado_por', 'prazos_provas_criado_por_foreign')
                        ->references('id')
                        ->on('users')
                        ->cascadeOnDelete();
                }

                if (! Schema::hasIndex('prazos_provas', ['disciplina_id', 'classe_id'])) {
                    $table->index(['disciplina_id', 'classe_id']);
                }

                if (! Schema::hasIndex('prazos_provas', ['status'])) {
                    $table->index('status');
                }

                if (! Schema::hasIndex('prazos_provas', ['data_limite'])) {
                    $table->index('data_limite');
                }

                if (! Schema::hasIndex('prazos_provas', ['ano_letivo'])) {
                    $table->index('ano_letivo');
                }
            });

            return;
        }

        Schema::create('prazos_provas', function (Blueprint $table) {
            $table->uuid('id')->primary(); // chave primária UUID

            $table->uuid('disciplina_id')->nullable();
            $table->uuid('classe_id')->nullable();    // em vez de turma_id
            $table->uuid('criado_por'); // diretor (users)

            // Dados do prazo
            $table->enum('tipo_prova', ['Prova-Trimestral', 'Exame-especial', 'Recurso'])->default('Prova-Trimestral');
            $table->string('titulo', 255)->nullable();
            $table->text('observacoes')->nullable();

            $table->timestamp('data_inicio');
            $table->timestamp('data_limite');

            $table->string('ano_letivo', 9);
            $table->string('periodo', 20);

            $table->enum('status', ['aberto', 'fechado', 'expirado'])->default('aberto');
            $table->boolean('permite_reenvio')->default(true);

            $table->timestamps();

            // Foreign keys
            $table->foreign('classe_id')->references('id')->on('classes')->onDelete('set null');
            $table->foreign('criado_por')->references('id')->on('users')->onDelete('cascade');

            // Índices
            $table->index(['disciplina_id', 'classe_id']);
            $table->index('status');
            $table->index('data_limite');
            $table->index('ano_letivo');
        });

        if (Schema::hasTable('disciplinas') && ! $this->foreignKeyExists('prazos_provas', 'disciplina_id')) {
            Schema::table('prazos_provas', function (Blueprint $table): void {
                $table->foreign('disciplina_id')
                    ->references('id')
                    ->on('disciplinas')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prazos_provas');
    }
};
