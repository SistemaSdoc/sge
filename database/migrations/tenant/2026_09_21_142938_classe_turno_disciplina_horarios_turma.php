<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema; {
    /**
     * Run the migrations.
     */
    return new class extends Migration {
        public function up(): void
        {
            Schema::table('classe_turno_disciplina_horarios', function (Blueprint $table) {
                $table->uuid('turma_id')->nullable()->after('classe_turno_disciplina_id');

                $table->foreign('turma_id', 'fk_ctdh_turma')
                    ->references('id')
                    ->on('turmas')
                    ->nullOnDelete();

                $table->index(['classe_turno_disciplina_id', 'turma_id', 'dia_semana'], 'idx_ctdh_turma_dia');
            });
        }

        public function down(): void
        {
            Schema::table('classe_turno_disciplina_horarios', function (Blueprint $table) {
                $table->dropForeign('fk_ctdh_turma');
                $table->dropIndex('idx_ctdh_turma_dia');
                $table->dropColumn('turma_id');
            });
        }
    };
}
;
