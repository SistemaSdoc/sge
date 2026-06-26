<?php

namespace App\Services\Pauta\Generators;

use App\Models\Turma;
use App\Models\TurmaAluno;
use App\Services\Core\RegraAcademicaService as CoreRegraAcademicaService;
use App\Services\Pauta\Concerns\CarregaDisciplinas;
use App\Services\Pauta\Concerns\ResolveSituacaoNota;

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
            'notas',
        ])
            ->where('turma_id', $turma->id)
            ->where('activo', true)
            ->whereIn('resultado', ['recurso', 'aprovado_recurso', 'reprovado_recurso']);

        if ($filtro) {
            // 'pendente' é o filtro do card Incompletos — mapeia para resultado='recurso'
            $resultado = $filtro === 'pendente' ? 'recurso' : $filtro;
            $query->where('resultado', $resultado);
        }

        $paginator = $query->paginate($perPage, ['*'], 'page_pautas');

        // Calcular disciplinas em recurso ANTES de montar os alunos
        // usando directamente o resultado académico de cada turma_aluno
        $idsEmRecurso = collect();
        foreach ($paginator->items() as $ta) {
            $resultadoFinal = $this->regraAcademicaService->avaliarAluno($ta);
            $ids = collect($resultadoFinal['detalhes'])
                ->where('situacao', 'recurso')
                ->pluck('disciplina_id');
            $idsEmRecurso = $idsEmRecurso->merge($ids);
        }

        $disciplinasEmRecurso = $disciplinas
            ->filter(fn ($d) => $idsEmRecurso->unique()->contains($d['id']))
            ->map(fn ($d) => ['id' => $d['id'], 'sigla' => $d['sigla'], 'nome' => $d['nome']])
            ->values();

        $alunos = $paginator->through(
            function ($ta) use ($disciplinas, &$offset) {
                $offset++;

                return $this->montarAluno($ta, $offset, $disciplinas);
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

    private function montarAluno($ta, int $numero, $disciplinas): array
    {
        $resultadoFinal = $this->regraAcademicaService->avaliarAluno($ta);
        $resultadoRecurso = $this->regraAcademicaService->avaliarRecurso($ta);

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
                        't1' => $notasDisciplina->firstWhere('periodo', 1)?->media_trimestral,
                        't2' => $notasDisciplina->firstWhere('periodo', 2)?->media_trimestral,
                        't3' => $notasDisciplina->firstWhere('periodo', 3)?->media_trimestral,
                        'mf' => $detFinal['media_final'] ?? null,
                        'nota_recurso' => $nota4?->media_trimestral,
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
