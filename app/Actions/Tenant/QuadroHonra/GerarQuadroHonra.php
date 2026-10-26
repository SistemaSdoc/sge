<?php

namespace App\Actions\Tenant\QuadroHonra;

use App\Enums\TipoQuadroHonra;
use App\Models\Tenant\QuadroHonra;
use App\Models\Tenant\User;
use App\Services\Tenant\QuadroHonra\CalcularCandidatosQuadroHonra;
use App\Services\Tenant\QuadroHonra\ResolverCriteriosQuadroHonra;
use Illuminate\Support\Facades\DB;

/** Gera ou actualiza o quadro e substitui os seus itens numa única transacção. */
class GerarQuadroHonra
{
    public function __construct(
        private readonly CalcularCandidatosQuadroHonra $calcularCandidatos,
        private readonly ResolverCriteriosQuadroHonra $resolverCriterios,
    ) {}

    /**
     * @param  array{tipo: string, ano_lectivo_id: string, curso_tutelado_id?: ?string, media_minima?: numeric-string|float|int, limite_alunos?: numeric-string|int}  $dados
     */
    public function handle(array $dados, User $utilizador): QuadroHonra
    {
        $tipo = TipoQuadroHonra::from($dados['tipo']);
        $cursoTuteladoId = $tipo === TipoQuadroHonra::CURSO
            ? $dados['curso_tutelado_id']
            : null;
        $escopo = $tipo === TipoQuadroHonra::GERAL
            ? TipoQuadroHonra::GERAL->value
            : 'curso:'.$cursoTuteladoId;
        $criterios = $this->resolverCriterios->resolver(
            isset($dados['media_minima']) ? (float) $dados['media_minima'] : null,
            isset($dados['limite_alunos']) ? (int) $dados['limite_alunos'] : null,
        );

        if ($utilizador->instituicao_id === null) {
            abort(403, 'O utilizador não está associado a uma instituição.');
        }

        return DB::transaction(function () use (
            $dados,
            $utilizador,
            $tipo,
            $cursoTuteladoId,
            $escopo,
            $criterios,
        ): QuadroHonra {
            $candidatos = $this->calcularCandidatos->calcular(
                $dados['ano_lectivo_id'],
                $utilizador->instituicao_id,
                $cursoTuteladoId,
                $criterios['media_minima'],
                $criterios['limite_alunos'],
            );

            $quadro = QuadroHonra::query()->firstOrNew([
                'ano_lectivo_id' => $dados['ano_lectivo_id'],
                'escopo' => $escopo,
            ]);
            $quadro->fill([
                'tipo' => $tipo,
                'curso_tutelado_id' => $cursoTuteladoId,
                'media_minima' => $criterios['media_minima'],
                'limite_alunos' => $criterios['limite_alunos'],
                'gerado_por' => $utilizador->id,
                'gerado_em' => now(),
            ]);
            $quadro->save();
            $quadro->itens()->delete();

            $quadro->itens()->createMany(
                $candidatos
                    ->values()
                    ->map(fn (array $candidato, int $index): array => [
                        ...$candidato,
                        'posicao' => $index + 1,
                    ])
                    ->all(),
            );

            return $quadro->load('itens');
        });
    }
}
