<?php

namespace App\Actions\Tenant\Pap\Grupo;

use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Turma;
use App\Services\Tenant\Pap\Tema\TemaPapUnicidadeService;
use Illuminate\Support\Facades\DB;

class CreateGrupoPap
{
    /**
     * Recebe o serviço que valida a unicidade do tema e do estudo de caso.
     */
    public function __construct(private readonly TemaPapUnicidadeService $temaPapUnicidadeService) {}

    /**
     * Cria um grupo PAP e regista os seus elementos.
     *
     * @param  array<string, mixed>  $dados
     */
    public function handle(Turma $turma, array $dados): GrupoPap
    {
        return DB::transaction(function () use ($turma, $dados): GrupoPap {
            $turma->loadMissing('cursoClasseTurno.cursoClasse');
            $cursoTuteladoId = (string) $turma->cursoClasseTurno?->cursoClasse?->curso_tutelado_id;
            $cursoClasseTurnoId = (string) $turma->curso_classe_turno_id;

            $this->temaPapUnicidadeService->validarUnicidade(
                $dados,
                $cursoTuteladoId,
                (string) $turma->ano_lectivo_id,
                $cursoClasseTurnoId,
            );

            $grupoPap = GrupoPap::create([
                'turma_id' => $turma->getKey(),
                'professor_tutor_id' => $dados['professor_tutor_id'] ?? null,
                'nome_grupo' => $dados['nome_grupo'],
                'status_aprovacao' => GrupoPap::APROVACAO_RASCUNHO,
                'tema_grupo' => $dados['tema_grupo'] ?? null,
                'problema' => $dados['problema'] ?? null,
                'objectivo_geral' => $dados['objectivo_geral'] ?? null,
                'objectivos_especificos' => $dados['objectivos_especificos'] ?? null,
                'estudo_caso' => $dados['estudo_caso'] ?? null,
                'nota_final' => $dados['nota_final'] ?? null,
                'data_defesa' => $dados['data_defesa'] ?? null,
            ]);

            $grupoPap->elementos()->createMany(
                collect($dados['alunos'])->map(fn (string $alunoId): array => [
                    'aluno_id' => $alunoId,
                ])->all()
            );

            return $grupoPap;
        });
    }
}
