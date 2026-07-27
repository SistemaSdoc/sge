<?php

namespace App\Services\Pauta\Generators;

use App\Models\Turma;
use App\Models\TurmaAluno;
use App\Services\Core\RegraAcademicaService;
use App\Services\Pauta\Concerns\CarregaDisciplinas;
use App\Services\Pauta\Concerns\ResolveSituacaoNota;
use Illuminate\Support\Collection;

class PautaFinalGenerator
{
    use CarregaDisciplinas, ResolveSituacaoNota;

    public function __construct(
        private readonly RegraAcademicaService $regraAcademicaService
    ) {}

    public function gerar(Turma $turma, int $perPage = 20, ?string $filtro = null): array
    {
        $disciplinas = $this->carregarDisciplinas($turma);

        $query = TurmaAluno::with([
            'aluno.inscricao.candidato:id,nome',
            'notas' => fn ($q) => $q->whereIn('periodo', [1, 2, 3, 4]),
        ])
            ->where('turma_id', $turma->id)
            ->where('activo', true);

        if ($filtro) {
            $query->where('resultado', $filtro);
        }

        $paginator = $query->paginate($perPage, ['*'], 'page_pautas');

        $offset = ($paginator->currentPage() - 1) * $paginator->perPage();

        $alunos = $paginator->through(
            function ($ta) use ($disciplinas, &$offset) {
                $offset++;

                return $this->montarAluno($ta, $offset, $disciplinas);
            }
        );

        return [
            'turma' => ['id' => $turma->id, 'nome' => $turma->nome],
            'periodo' => 'final',
            'tipo' => 'final',
            'disciplinas' => $disciplinas->map(fn ($d) => [
                'id' => $d['id'],
                'sigla' => $d['sigla'],
                'nome' => $d['nome'],
            ])->values(),
            'resumo' => $this->calcularResumo($turma),
            'alunos' => $alunos,
        ];
    }

    private function montarAluno($ta, int $numero, Collection $disciplinas): array
    {
        $resultadoAcademico = $this->regraAcademicaService
            ->resolverSituacaoAcademica($ta);

        $notasPorTdp = $ta->notas->groupBy('turma_disciplina_professor_id');

        $notas = $disciplinas->mapWithKeys(
            function ($disc) use ($notasPorTdp, $resultadoAcademico) {
                $notasDisciplina = $notasPorTdp->get($disc['tdp_id'], collect());
                $detalhe = collect($resultadoAcademico['detalhes'])
                    ->firstWhere('disciplina_id', $disc['id']);

                $nota3 = $notasDisciplina->firstWhere('periodo', 3);

                return [
                    $disc['id'] => [
                        't1' => $notasDisciplina->firstWhere('periodo', 1)?->media_trimestral,
                        't2' => $notasDisciplina->firstWhere('periodo', 2)?->media_trimestral,
                        't3' => $nota3?->media_trimestral,
                        'mf' => $nota3?->media_final,
                        'total_faltas' => $notasDisciplina->whereIn('periodo', [1, 2, 3])->sum('faltas'),
                        'nota_recurso' => $notasDisciplina->firstWhere('periodo', 4)?->media_trimestral,
                        'situacao' => $this->resolverSituacao($nota3?->media_final, $detalhe['situacao'] ?? null),
                    ],
                ];
            }
        );

        return [
            'numero' => $numero,
            'aluno_id' => $ta->aluno->id,
            'nome' => $ta->aluno->inscricao?->candidato?->nome,
            'situacao' => $ta->situacao,
            'notas' => $notas,
            'resultado' => $resultadoAcademico['situacao'],
            'deficiencias' => collect($resultadoAcademico['detalhes'])
                ->where('situacao', 'transita_com_deficiencia')
                ->pluck('disciplina_id')
                ->values(),
            'disciplinas_recurso' => collect($resultadoAcademico['detalhes'])
                ->where('situacao', 'recurso')
                ->pluck('disciplina_id')
                ->values(),
        ];
    }

    private function calcularResumo(Turma $turma): array
    {
        $counts = TurmaAluno::where('turma_id', $turma->id)
            ->where('activo', true)
            ->selectRaw('resultado, COUNT(*) as total')
            ->groupBy('resultado')
            ->pluck('total', 'resultado');

        return [
            'total' => $counts->sum(),
            'transita' => $counts->get('transita', 0),
            'transita_com_deficiencia' => $counts->get('transita_com_deficiencia', 0),
            'recurso' => $counts->get('recurso', 0),
            'reprovados' => $counts->get('reprovado', 0),
            'EEF' => $counts->get('EEF', 0),
            'incompletos' => $counts->get('incompleto', 0),
        ];
    }
}
