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
        Schema::create('regras_avaliacao', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('instituicao_id')->constrained('instituicoes');
            $table->foreignUuid('ano_lectivo_id')->constrained('ano_lectivos');

            // Nível de resolução — null em ambos = regra geral da instituição
            $table->string('nivel_ensino')->nullable();          // ex: 'primario', 'i_ciclo'
            $table->foreignUuid('classe_id')->nullable()->constrained('classes'); // override pontual

            // Fórmula de cálculo
            $table->enum('formula_calculo', ['simples', 'ponderada'])->default('simples');
            $table->json('pesos')->nullable(); // {"t1":30,"t2":30,"t3":40} — só se ponderada

            // Critérios de aprovação
            $table->decimal('media_minima_aprovacao', 4, 1)->default(10);
            $table->decimal('media_minima_recurso', 4, 1)->default(8);    // abaixo disto = reprovado directo
            $table->unsignedTinyInteger('max_disciplinas_recurso')->default(3);

            // Recurso
            $table->boolean('permite_recurso')->default(true);
            $table->decimal('nota_minima_recurso', 4, 1)->default(10);
            $table->enum('formula_recurso', ['so_exame', 'media'])->default('so_exame');

            // Assiduidade
            $table->boolean('considerar_faltas')->default(true);
            $table->unsignedTinyInteger('frequencia_minima')->default(75); // percentagem
            $table->boolean('excluir_por_faltas')->default(true);

            $table->timestamps();

            // Garante uma regra por escopo/ano — não pode haver dois registos iguais
            $table->unique(['instituicao_id', 'ano_lectivo_id', 'nivel_ensino', 'classe_id'], 'regra_escopo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regras_avaliacao');
    }
};
