<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('submissoes_provas')) {
            $foreignKeys = collect(Schema::getForeignKeys('submissoes_provas'))
                ->pluck('name')
                ->all();
            $indexes = collect(Schema::getIndexes('submissoes_provas'))
                ->pluck('name')
                ->all();

            Schema::table('submissoes_provas', function (Blueprint $table) use ($foreignKeys, $indexes): void {
                if (! in_array('submissoes_provas_classe_id_foreign', $foreignKeys, true)) {
                    $table->foreign('classe_id', 'submissoes_provas_classe_id_foreign')
                        ->references('id')
                        ->on('classes')
                        ->nullOnDelete();
                }

                if (! in_array('submissoes_provas_prazo_prova_id_estado_index', $indexes, true)) {
                    $table->index(['prazo_prova_id', 'estado'], 'submissoes_provas_prazo_prova_id_estado_index');
                }

                if (! in_array('submissoes_provas_disciplina_id_index', $indexes, true)) {
                    $table->index('disciplina_id', 'submissoes_provas_disciplina_id_index');
                }

                if (! in_array('submissoes_provas_prazo_prova_id_professor_id_versao_unique', $indexes, true)) {
                    $table->unique(
                        ['prazo_prova_id', 'professor_id', 'versao'],
                        'submissoes_provas_prazo_prova_id_professor_id_versao_unique'
                    );
                }
            });

            return;
        }

        Schema::create('submissoes_provas', function (Blueprint $table) {
            $table->uuid('id')->primary(); // auto-increment para esta tabela (opcional, mas mantém)

            // Chaves estrangeiras (UUID)
            $table->uuid('prazo_prova_id');      // referencia prazos_provas(id)
            $table->uuid('professor_id');        // referencia professores(id)
            $table->uuid('disciplina_id');
            $table->uuid('classe_id')->nullable();

            // Ficheiros
            $table->string('caminho_prova');
            $table->string('caminho_chave');

            // Versão e comentários
            $table->unsignedSmallInteger('versao')->default(1);
            $table->text('comentario_professor')->nullable();

            // Estados
            $table->enum('estado', [
                'pendente', 'em_revisao', 'aprovado', 'rejeitado', 'substituido',
            ])->default('pendente');

            $table->text('parecer_diretor')->nullable();

            $table->timestamp('data_submissao')->useCurrent();
            $table->timestamps();

            // FOREIGN KEYS
            $table->foreign('prazo_prova_id')->references('id')->on('prazos_provas')->onDelete('cascade');
            $table->foreign('professor_id')->references('id')->on('professores')->onDelete('cascade');
            $table->foreign('classe_id')->references('id')->on('classes')->onDelete('set null');

            // Índices
            $table->index(['prazo_prova_id', 'estado']);
            $table->index('professor_id');
            $table->index('disciplina_id');

            // Garantir que não haja duas versões iguais para o mesmo professor/prazo
            $table->unique(['prazo_prova_id', 'professor_id', 'versao']);
        });

        if (Schema::hasTable('disciplinas')) {
            Schema::table('submissoes_provas', function (Blueprint $table): void {
                $table->foreign('disciplina_id')
                    ->references('id')
                    ->on('disciplinas')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('submissoes_provas');
    }
};
