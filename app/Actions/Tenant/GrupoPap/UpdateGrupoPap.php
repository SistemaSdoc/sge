<?php

namespace App\Actions\Tenant\GrupoPap;

use App\Models\Tenant\GrupoPap;
use App\Services\Tenant\TemaPapUnicidadeService;
use Illuminate\Support\Facades\DB;

/**
 * Actualiza os dados e os elementos de um grupo PAP.
 */
class UpdateGrupoPap
{
    public function __construct(private readonly TemaPapUnicidadeService $temaPapUnicidadeService) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(GrupoPap $grupoPap, array $validated): void
    {
        DB::transaction(function () use ($grupoPap, $validated): void {
            if (array_key_exists('tema_grupo', $validated) || array_key_exists('estudo_caso', $validated)) {
                $grupoPap->loadMissing('turma.cursoClasseTurno.cursoClasse');
                $turma = $grupoPap->turma;
                $cursoTuteladoId = (string) $turma?->cursoClasseTurno?->cursoClasse?->curso_tutelado_id;
                $cursoClasseTurnoId = (string) $turma?->curso_classe_turno_id;
                $dadosTemaCaso = [
                    'tema_grupo' => array_key_exists('tema_grupo', $validated)
                        ? $validated['tema_grupo']
                        : $grupoPap->tema_grupo,
                    'estudo_caso' => array_key_exists('estudo_caso', $validated)
                        ? $validated['estudo_caso']
                        : $grupoPap->estudo_caso,
                ];

                $this->temaPapUnicidadeService->validarUnicidade(
                    $dadosTemaCaso,
                    $cursoTuteladoId,
                    (string) $turma?->ano_lectivo_id,
                    $cursoClasseTurnoId,
                    (string) $grupoPap->getKey(),
                );
            }

            $grupoPap->update(array_intersect_key($validated, array_flip([
                'nome_grupo',
                'tema_grupo',
                'estudo_caso',
                'status',
                'nota_final',
                'data_defesa',
                'professor_tutor_id',
            ])));

            if (\array_key_exists('alunos', $validated)) {
                $grupoPap->alunos()->sync($validated['alunos']);
            }
        });
    }
}
