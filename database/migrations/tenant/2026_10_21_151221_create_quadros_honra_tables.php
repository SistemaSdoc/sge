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
        // A tabela do ano lectivo pertence ao banco central e não aceita FK entre bancos.
        if (! Schema::hasTable('quadros_honra')) {
            Schema::create('quadros_honra', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('ano_lectivo_id');
                $table->string('tipo', 20);
                $table->uuid('curso_tutelado_id')->nullable();
                $table->string('escopo', 80);
                $table->decimal('media_minima', 4, 2)->default(14);
                $table->unsignedSmallInteger('limite_alunos')->default(10);
                $table->uuid('gerado_por')->nullable();
                $table->timestamp('gerado_em');
                $table->timestamps();
            });
        }

        // Verifica o esquema para retomar com segurança uma execução interrompida pelo MySQL.
        $indexes = array_column(Schema::getIndexes('quadros_honra'), 'name');
        $foreignKeys = array_column(Schema::getForeignKeys('quadros_honra'), 'name');

        Schema::table('quadros_honra', function (Blueprint $table) use ($indexes, $foreignKeys): void {
            if (! in_array('quadros_honra_curso_tutelado_id_foreign', $foreignKeys, true)) {
                $table->foreign('curso_tutelado_id')
                    ->references('id')
                    ->on('curso_tutelado')
                    ->nullOnDelete();
            }

            if (! in_array('quadros_honra_gerado_por_foreign', $foreignKeys, true)) {
                $table->foreign('gerado_por')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }

            if (! in_array('quadros_honra_ano_lectivo_id_index', $indexes, true)) {
                $table->index('ano_lectivo_id');
            }

            if (! in_array('quadros_honra_ano_escopo_unique', $indexes, true)) {
                $table->unique(['ano_lectivo_id', 'escopo'], 'quadros_honra_ano_escopo_unique');
            }
        });

        if (! Schema::hasTable('quadro_honra_itens')) {
            Schema::create('quadro_honra_itens', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('quadro_honra_id')->constrained('quadros_honra')->cascadeOnDelete();
                $table->foreignUuid('turma_aluno_id')->nullable()->constrained('turma_aluno')->nullOnDelete();
                $table->foreignUuid('aluno_id')->nullable()->constrained('alunos')->nullOnDelete();
                $table->unsignedSmallInteger('posicao');
                $table->string('nome_aluno');
                $table->string('matricula')->nullable();
                $table->string('classe');
                $table->string('curso');
                $table->string('turma');
                $table->decimal('media', 5, 2);
                $table->unsignedInteger('faltas_total')->default(0);
                $table->unsignedSmallInteger('notas_abaixo_dez')->default(0);
                $table->timestamps();

                $table->unique(['quadro_honra_id', 'posicao'], 'quadro_honra_itens_posicao_unique');
                $table->index(['quadro_honra_id', 'aluno_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quadro_honra_itens');
        Schema::dropIfExists('quadros_honra');
    }
};
