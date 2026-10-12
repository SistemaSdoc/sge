<?php

namespace App\Services\Tenant\Pauta\Generators;

use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaAluno;
use App\Services\Tenant\Core\RegraAcademicaService as CoreRegraAcademicaService;
use App\Services\Tenant\Pauta\Concerns\CarregaDisciplinas;
use App\Services\Tenant\Pauta\Concerns\ResolveSituacaoNota;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PautaRecursoGenerator
{
    use CarregaDisciplinas, ResolveSituacaoNota;

    public function __construct(
        private readonly CoreRegraAcademicaService $regraAcademicaService
    ) {}

    public function gerar(Turma $turma, int $perPage = 20, ?string $filtro = null): array
    {
        $disciplinas = $this->carregarDisciplinas($turma);

        $turmaAlunos = $this->queryAlunosRecurso($turma)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $resultadosCache = [];
        $idsEmRecurso = collect();

        $alunosElegiveis = $turmaAlunos->filter(function (TurmaAluno $ta) use (&$resultadosCache, &$idsEmRecurso): bool {
            $resultadoRecurso = $this->regraAcademicaService->resolverSituacaoRecurso($ta);

            if ($resultadoRecurso['detalhes'] === []) {
                return false;
            }

            $resultadosCache[$ta->id] = $resultadoRecurso;
            $ids = collect($resultadoRecurso['detalhes'])
                ->pluck('disciplina_id');
            $idsEmRecurso = $idsEmRecurso->merge($ids);

            return true;
        })->values();

        $disciplinasEmRecurso = $disciplinas
            ->filter(fn ($d) => $idsEmRecurso->unique()->contains($d['id']))
            ->map(fn ($d) => [
                'id' => $d['id'],
                'classe_turno_disciplina_id' => $d['classe_turno_disciplina_id'],
                'sigla' => $d['sigla'],
                'nome' => $d['nome'],
                'professor' => $d['professor'] ?? null, // ✅ adicionar
            ])
            ->values();

        $linhasAlunos = $alunosElegiveis
            ->values()
            ->map(fn (TurmaAluno $ta, int $index) => $this->montarAluno(
                $ta,
                $index + 1,
                $disciplinas,
                $resultadosCache[$ta->id],
            ));
        $filtroResultado = in_array($filtro, ['pendente', 'recurso'], true)
            ? 'pendente'
            : $filtro;
        $alunosFiltrados = $filtroResultado
            ? $linhasAlunos->where('resultado', $filtroResultado)->values()
            : $linhasAlunos;
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
            'periodo' => 4,
            'tipo' => 'recurso',
            'disciplinas' => $disciplinasEmRecurso,
            'resumo' => $this->calcularResumo($linhasAlunos),
            'alunos' => $alunos,
        ];
    }

    private function montarAluno($ta, int $numero, $disciplinas, array $resultadoRecurso): array
    {
        $disciplinasNegativas = collect($resultadoRecurso['detalhes'])
            ->keyBy('disciplina_id');

        $notasPeriodo4 = $ta->notas
            ->where('periodo', 4)
            ->keyBy('turma_disciplina_professor_id');

        $notas = $disciplinas
            ->filter(fn ($d) => $disciplinasNegativas->has($d['id']))
            ->mapWithKeys(function ($d) use ($ta, $notasPeriodo4, $disciplinasNegativas) {
                $notasDisciplina = $ta->notas->where('turma_disciplina_professor_id', $d['tdp_id']);
                $nota4 = $notasPeriodo4->get($d['tdp_id']);
                $detRecurso = $disciplinasNegativas->get($d['id']);

                return [
                    $d['id'] => [
                        't1' => $this->arredondarNota($notasDisciplina->firstWhere('periodo', 1)?->media_trimestral),
                        't2' => $this->arredondarNota($notasDisciplina->firstWhere('periodo', 2)?->media_trimestral),
                        't3' => $this->arredondarNota($notasDisciplina->firstWhere('periodo', 3)?->media_trimestral),
                        'mf' => $this->arredondarNota($detRecurso['media_final'] ?? null),
                        'nota_recurso' => $this->arredondarNota($nota4?->media_trimestral),
                        'situacao' => $detRecurso['situacao'] ?? $this->resolverSituacao($nota4?->media_trimestral, null),
                    ],
                ];
            });

        return [
            'numero' => $numero,
            'aluno_id' => $ta->aluno->id,
            'turma_aluno_id' => $ta->id,
            'nome' => $ta->aluno->inscricao?->candidato?->nome,
            'notas' => $notas,
            'resultado' => $resultadoRecurso['situacao'] ?? 'pendente',
        ];
    }

    private function calcularResumo(Collection $alunos): array
    {
        $counts = $alunos->countBy('resultado');

        return [
            'total' => $alunos->count(),
            'transita' => $counts->get('aprovado_recurso', 0),
            'nao_transita' => $counts->get('reprovado_recurso', 0),
            'incompletos' => $counts->get('pendente', 0),
        ];
    }

    private function queryAlunosRecurso(Turma $turma)
    {
        return TurmaAluno::with([
            'aluno.inscricao.candidato:id,nome',
            'notas.turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
            'turma.turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
            'turma.cursoClasseTurno.cursoClasse.classe',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
        ])
            ->where('turma_id', $turma->id)
            ->where('activo', true);
    }
}
