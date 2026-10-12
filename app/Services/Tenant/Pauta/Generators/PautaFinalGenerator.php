<?php

namespace App\Services\Tenant\Pauta\Generators;

use App\Models\Tenant\Nota;
use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaAluno;
use App\Services\Tenant\Pauta\Concerns\CarregaDisciplinas;
use App\Services\Tenant\Pauta\Concerns\ResolveSituacaoNota;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PautaFinalGenerator
{
    use CarregaDisciplinas, ResolveSituacaoNota;

    public function gerar(Turma $turma, int $perPage = 20, ?string $filtro = null): array
    {
        $disciplinas = $this->carregarDisciplinas($turma);

        $query = TurmaAluno::with([
            'aluno.inscricao.candidato:id,nome',
            'notas' => fn ($q) => $q->whereIn('periodo', [1, 2, 3]),
        ])
            ->where('turma_id', $turma->id)
            ->where('activo', true)
            ->orderBy('created_at')
            ->orderBy('id');

        $alunosCalculados = $query->get()
            ->values()
            ->map(fn ($ta, int $index) => $this->montarAluno($ta, $index + 1, $disciplinas));
        $alunosFiltrados = $filtro
            ? $alunosCalculados->where('resultado', $filtro)->values()
            : $alunosCalculados;
        $pagina = LengthAwarePaginator::resolveCurrentPage('page_pautas');
        $alunos = new LengthAwarePaginator(
            $alunosFiltrados->forPage($pagina, $perPage)->values(),
            $alunosFiltrados->count(),
            $perPage,
            $pagina,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => request()->query(),
                'pageName' => 'page_pautas',
            ],
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
            'resumo' => $this->calcularResumo($alunosCalculados),
            'alunos' => $alunos,
        ];
    }

    private function montarAluno($ta, int $numero, Collection $disciplinas): array
    {
        $notasPorTdp = $ta->notas->groupBy('turma_disciplina_professor_id');

        $notas = $disciplinas->mapWithKeys(
            function ($disc) use ($notasPorTdp) {
                $notasDisciplina = $notasPorTdp->get($disc['tdp_id'], collect());

                $notasTrimestrais = collect([1, 2, 3])
                    ->map(fn (int $periodo) => $notasDisciplina->firstWhere('periodo', $periodo)?->media_trimestral);
                $mediaFinalTrimestral = $notasTrimestrais->contains(fn ($media): bool => $media === null)
                    ? null
                    : round($notasTrimestrais->avg(), 1, PHP_ROUND_HALF_UP);
                $situacaoDisciplina = $mediaFinalTrimestral === null
                    ? null
                    : ($mediaFinalTrimestral >= Nota::NOTA_MINIMA_APTO ? 'transita' : 'recurso');

                return [
                    $disc['id'] => [
                        't1' => $this->arredondarNota($notasDisciplina->firstWhere('periodo', 1)?->media_trimestral),
                        't2' => $this->arredondarNota($notasDisciplina->firstWhere('periodo', 2)?->media_trimestral),
                        't3' => $this->arredondarNota($notasDisciplina->firstWhere('periodo', 3)?->media_trimestral),
                        'mf' => $this->arredondarNota($mediaFinalTrimestral),
                        'total_faltas' => $notasDisciplina->whereIn('periodo', [1, 2, 3])->sum('faltas'),
                        'situacao' => $this->resolverSituacao($mediaFinalTrimestral, $situacaoDisciplina),
                    ],
                ];
            }
        );
        $resultado = $notas->isEmpty() || $notas->contains(fn (array $nota): bool => $nota['mf'] === null)
            ? 'incompleto'
            : ($notas->contains('situacao', 'recurso') ? 'recurso' : 'transita');

        return [
            'numero' => $numero,
            'aluno_id' => $ta->aluno->id,
            'nome' => $ta->aluno->inscricao?->candidato?->nome,
            'situacao' => $ta->situacao,
            'notas' => $notas,
            'resultado' => $resultado,
            'deficiencias' => collect(),
            'disciplinas_recurso' => $notas
                ->where('situacao', 'recurso')
                ->keys()
                ->values(),
        ];
    }

    private function calcularResumo(Collection $alunos): array
    {
        $counts = $alunos->countBy('resultado');

        return [
            'total' => $alunos->count(),
            'transita' => $counts->get('transita', 0),
            'transita_com_deficiencia' => $counts->get('transita_com_deficiencia', 0),
            'recurso' => $counts->get('recurso', 0),
            'reprovados' => $counts->get('reprovado', 0) + $counts->get('reprovado_negativas', 0),
            'EEF' => $counts->get('EEF', 0),
            'incompletos' => $counts->get('incompleto', 0),
        ];
    }
}
