<?php

namespace App\Services\Tenant\Pauta\Generators;

use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaAluno;
use App\Services\Tenant\Core\RegraAcademicaService as CoreRegraAcademicaService;
use App\Services\Tenant\Pauta\Concerns\CarregaDisciplinas;
use App\Services\Tenant\Pauta\Concerns\ResolveSituacaoNota;

class PautaRecursoGenerator
{
    use CarregaDisciplinas, ResolveSituacaoNota;

    public function __construct(
        private readonly CoreRegraAcademicaService $regraAcademicaService
    ) {}

    public function gerar(Turma $turma, int $perPage = 20, ?string $filtro = null): array
    {
        $disciplinas = $this->carregarDisciplinas($turma);

        $query = TurmaAluno::with([
            'aluno.inscricao.candidato:id,nome',
            'notas.turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
            'turma.turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
            'turma.cursoClasseTurno.cursoClasse.classe',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
        ])
            ->where('turma_id', $turma->id)
            ->where('activo', true)
            ->whereIn('resultado', ['recurso', 'aprovado_recurso', 'reprovado_recurso']);

        if ($filtro) {
            $resultado = $filtro === 'pendente' ? 'recurso' : $filtro;
            $query->where('resultado', $resultado);
        }

        $paginator = $query->paginate($perPage, ['*'], 'page_pautas');

        // Cache dos resultados — evita calcular 2x por aluno
        $resultadosCache = [];
        $idsEmRecurso = collect();

        foreach ($paginator->items() as $ta) {
            $resultadoFinal = $this->regraAcademicaService->resolverSituacaoAcademica($ta);
            $resultadosCache[$ta->id] = $resultadoFinal;
            $ids = collect($resultadoFinal['detalhes'])
                ->where('situacao', 'recurso')
                ->pluck('disciplina_id');
            $idsEmRecurso = $idsEmRecurso->merge($ids);
        }

        $disciplinasEmRecurso = $disciplinas
            ->filter(fn ($d) => $idsEmRecurso->unique()->contains($d['id']))
            ->map(fn ($d) => ['id' => $d['id'], 'sigla' => $d['sigla'], 'nome' => $d['nome']])
            ->values();

        $offset = 0;
        $alunos = $paginator->through(
            function ($ta) use ($disciplinas, $resultadosCache, &$offset) {
                $offset++;

                return $this->montarAluno($ta, $offset, $disciplinas, $resultadosCache[$ta->id]);
            }
        );

        return [
            'turma' => ['id' => $turma->id, 'nome' => $turma->nome],
            'periodo' => 4,
            'tipo' => 'recurso',
            'disciplinas' => $disciplinasEmRecurso,
            'resumo' => $this->calcularResumo($turma),
            'alunos' => $alunos,
        ];
    }

    private function montarAluno($ta, int $numero, $disciplinas, array $resultadoFinal): array
    {
        // resultadoFinal já vem do cache — sem query extra
        $resultadoRecurso = $this->regraAcademicaService->resolverSituacaoRecurso($ta, $resultadoFinal);

        $disciplinasNegativas = collect($resultadoFinal['detalhes'])
            ->where('situacao', 'recurso')
            ->keyBy('disciplina_id');

        $notasPeriodo4 = $ta->notas
            ->where('periodo', 4)
            ->keyBy('turma_disciplina_professor_id');

        $notas = $disciplinas
            ->filter(fn ($d) => $disciplinasNegativas->has($d['id']))
            ->mapWithKeys(function ($d) use ($ta, $notasPeriodo4, $disciplinasNegativas, $resultadoRecurso) {
                $notasDisciplina = $ta->notas->where('turma_disciplina_professor_id', $d['tdp_id']);
                $nota4 = $notasPeriodo4->get($d['tdp_id']);
                $detFinal = $disciplinasNegativas->get($d['id']);
                $detRecurso = collect($resultadoRecurso['detalhes'])->firstWhere('disciplina_id', $d['id']);

                return [
                    $d['id'] => [
                        't1' => $this->arredondarNota($notasDisciplina->firstWhere('periodo', 1)?->media_trimestral),
                        't2' => $this->arredondarNota($notasDisciplina->firstWhere('periodo', 2)?->media_trimestral),
                        't3' => $this->arredondarNota($notasDisciplina->firstWhere('periodo', 3)?->media_trimestral),
                        'mf' => $this->arredondarNota($detFinal['media_final'] ?? null),
                        'nota_recurso' => $this->arredondarNota($nota4?->media_trimestral),
                        'situacao' => $this->resolverSituacao($nota4?->media_trimestral, $detRecurso['situacao'] ?? null),
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

    private function calcularResumo(Turma $turma): array
    {
        $counts = TurmaAluno::where('turma_id', $turma->id)
            ->where('activo', true)
            ->whereIn('resultado', ['recurso', 'aprovado_recurso', 'reprovado_recurso'])
            ->selectRaw('resultado, COUNT(*) as total')
            ->groupBy('resultado')
            ->pluck('total', 'resultado');

        return [
            'total' => $counts->sum(),
            'transita' => $counts->get('aprovado_recurso', 0),
            'nao_transita' => $counts->get('reprovado_recurso', 0),
            'incompletos' => $counts->get('recurso', 0),
        ];
    }
}
