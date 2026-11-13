<?php

namespace App\Actions\Tenant\Pap\Grupo;

use App\Models\Tenant\GrupoPap;
use App\Services\Tenant\TemaPapUnicidadeService;
use Illuminate\Support\Facades\DB;

class UpdateGrupoPap
{
    /**
     * Recebe o serviço que valida a unicidade do tema e do estudo de caso.
     */
    public function __construct(private readonly TemaPapUnicidadeService $temaPapUnicidadeService) {}

    /**
     * Actualiza os dados e os elementos de um grupo PAP.
     *
     * @param  array<string, mixed>  $dados
     */
    public function handle(GrupoPap $grupoPap, array $dados): void
    {
        DB::transaction(function () use ($grupoPap, $dados): void {
            if (array_key_exists('tema_grupo', $dados) || array_key_exists('estudo_caso', $dados)) {
                $grupoPap->loadMissing('turma.cursoClasseTurno.cursoClasse');
                $turma = $grupoPap->turma;
                $cursoTuteladoId = (string) $turma?->cursoClasseTurno?->cursoClasse?->curso_tutelado_id;
                $cursoClasseTurnoId = (string) $turma?->curso_classe_turno_id;
                $dadosTemaCaso = [
                    'tema_grupo' => array_key_exists('tema_grupo', $dados)
                        ? $dados['tema_grupo']
                        : $grupoPap->tema_grupo,
                    'estudo_caso' => array_key_exists('estudo_caso', $dados)
                        ? $dados['estudo_caso']
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

            $grupoPap->update(array_intersect_key($dados, array_flip([
                'nome_grupo',
                'tema_grupo',
                'estudo_caso',
                'status',
                'nota_final',
                'data_defesa',
                'professor_tutor_id',
            ])));

            if (array_key_exists('alunos', $dados)) {
                $grupoPap->alunos()->sync($dados['alunos']);
            }
        });
    }
}
