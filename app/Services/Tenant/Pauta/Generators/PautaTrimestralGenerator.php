<?php

namespace App\Services\Pauta\Generators;

use App\Models\Nota;
use App\Models\Turma;
use App\Models\TurmaAluno;
use App\Models\TurmaDisciplinaProfessor;
use App\Services\Pauta\Concerns\CarregaDisciplinas;
use App\Services\Pauta\Concerns\ResolveSituacaoNota;
use Illuminate\Support\Collection;

class PautaTrimestralGenerator
{
    use CarregaDisciplinas, ResolveSituacaoNota;

    public function __construct(
        private readonly int $periodo
    ) {}

    public function gerar(Turma $turma, int $perPage = 20, ?string $filtro = null): array
    {
        $disciplinas = $this->carregarDisciplinas($turma);

        $query = TurmaAluno::with([
            'aluno.inscricao.candidato:id,nome',
            'notas' => fn ($q) => $q->where('periodo', $this->periodo),
        ])
            ->where('turma_id', $turma->id)
            ->where('activo', true);

        if ($filtro) {
            if ($filtro === 'sem_notas') {
                // Alunos que têm pelo menos uma disciplina SEM nota lançada
                // = alunos onde o count de notas do período < total de disciplinas
                $query->where(function ($q) use ($turma) {
                    $totalDisciplinas = TurmaDisciplinaProfessor::where('turma_id', $turma->id)->count();

                    $q->whereDoesntHave('notas', fn ($q) => $q->where('periodo', $this->periodo))
                        ->orWhereHas('notas', fn ($q) => $q
                            ->where('periodo', $this->periodo)
                            ->whereNull('media_trimestral')
                        );
                });

            } elseif ($filtro === 'APTO') {
                // Todas as disciplinas APTO e nenhuma sem nota
                $query->whereHas('notas', fn ($q) => $q
                    ->where('periodo', $this->periodo)
                    ->where('situacao_trimestral', 'APTO')
                )->whereDoesntHave('notas', fn ($q) => $q
                    ->where('periodo', $this->periodo)
                    ->where(fn ($q) => $q
                        ->where('situacao_trimestral', '!=', 'APTO')
                        ->orWhereNull('situacao_trimestral')
                    )
                );
            } elseif ($filtro === 'N/APTO') {
                // Pelo menos uma N/APTO e nenhuma sem nota
                $query->whereHas('notas', fn ($q) => $q
                    ->where('periodo', $this->periodo)
                    ->where('situacao_trimestral', 'N/APTO')
                )->whereDoesntHave('notas', fn ($q) => $q
                    ->where('periodo', $this->periodo)
                    ->whereNull('situacao_trimestral')
                );
            } else {
                $query->whereHas('notas', fn ($q) => $q
                    ->where('periodo', $this->periodo)
                    ->where('situacao_trimestral', $filtro)
                );
            }
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
            'periodo' => $this->periodo,
            'tipo' => 'trimestral',
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
        $notasPorTdp = $ta->notas->groupBy('turma_disciplina_professor_id');
        $totalDisciplinas = $disciplinas->count();

        $notas = $disciplinas->mapWithKeys(function ($disc) use ($notasPorTdp) {
            $nota = $notasPorTdp
                ->get($disc['tdp_id'], collect())
                ->firstWhere('periodo', $this->periodo);

            return [
                $disc['id'] => [
                    'media' => $this->arredondarNota($nota?->media_trimestral),
                    'faltas' => $nota?->faltas,
                    'situacao' => $this->resolverSituacao($nota?->media_trimestral, $nota?->situacao_trimestral),
                ],
            ];
        });

        $resultado = $this->resolverResultadoTrimestral($notas, $totalDisciplinas);

        return [
            'numero' => $numero,
            'aluno_id' => $ta->aluno->id,
            'nome' => $ta->aluno->inscricao?->candidato?->nome,
            'situacao' => $ta->situacao,
            'notas' => $notas,
            'resultado' => $resultado,
        ];
    }

    private function calcularResumo(Turma $turma): array
    {
        $totalAlunos = TurmaAluno::where('turma_id', $turma->id)
            ->where('activo', true)
            ->count();

        $disciplinas = $this->carregarDisciplinas($turma);
        $totalDisciplinas = $disciplinas->count();

        $turmaAlunos = TurmaAluno::with([
            'notas' => fn ($q) => $q->where('periodo', $this->periodo),
        ])
            ->where('turma_id', $turma->id)
            ->where('activo', true)
            ->get();

        $resumo = ['apto' => 0, 'nao_apto' => 0, 'EEF' => 0, 'incompletos' => 0];

        foreach ($turmaAlunos as $ta) {
            $notas = $disciplinas->mapWithKeys(function ($disc) use ($ta) {
                $nota = $ta->notas
                    ->where('turma_disciplina_professor_id', $disc['tdp_id'])
                    ->first();

                return [
                    $disc['id'] => [
                        'media' => $nota?->media_trimestral,
                        'situacao' => $nota?->situacao_trimestral,
                    ],
                ];
            });

            $resultado = $this->resolverResultadoTrimestral($notas, $totalDisciplinas);

            match ($resultado) {
                'APTO' => $resumo['apto']++,
                'N/APTO' => $resumo['nao_apto']++,
                'EEF' => $resumo['EEF']++,
                default => $resumo['incompletos']++,
            };
        }

        return $resumo;
    }

    private function resolverResultadoTrimestral(Collection $notas, int $totalDisciplinas): string
    {
        $lancadas = $notas->filter(fn ($n) => $n['media'] !== null);

        if ($lancadas->isEmpty()) {
            return '';
        }

        if ($lancadas->count() < $totalDisciplinas) {
            return 'INCOMPLETO';
        }

        // EEF tem prioridade
        if ($notas->contains(fn ($n) => $n['situacao'] === 'EEF')) {
            return 'EEF';
        }

        $mediaGeral = round($lancadas->avg('media'), 1, PHP_ROUND_HALF_UP);

        return $mediaGeral >= 10 ? 'APTO' : 'N/APTO';
    }
}
