<?php

namespace App\Services\Tenant\Pauta\Exports\Trimestral;

use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaAluno;
use App\Models\Tenant\TurmaDisciplinaProfessor;
use App\Services\Tenant\Pauta\PautaService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * Prepara disciplinas, alunos e notas para o template trimestral.
 */
final class PautaTrimestralTemplateDataProvider
{
    /**
     * Injeta o serviço que calcula os resultados trimestrais.
     */
    public function __construct(private readonly PautaService $pautaService) {}

    /**
     * Obtém as disciplinas distintas usadas na turma.
     *
     * @return list<array{id: string, name: string, sigla: string, tdp_id: string}>
     */
    public function prepararDisciplinas(Turma $turma): array
    {
        /** @var EloquentCollection<int, TurmaDisciplinaProfessor> $turmaDisciplinas */
        $turmaDisciplinas = TurmaDisciplinaProfessor::query()
            ->with('classeTurnoDisciplina.disciplina')
            ->where('turma_id', $turma->getKey())
            ->get()
            ->unique('classe_turno_disciplina_id')
            ->values();

        $disciplinas = [];

        foreach ($turmaDisciplinas as $turmaDisciplinaProfessor) {
            $disciplina = $turmaDisciplinaProfessor->classeTurnoDisciplina?->disciplina;

            if ($disciplina === null) {
                continue;
            }

            $disciplinas[] = [
                'id' => (string) $disciplina->getKey(),
                'name' => $disciplina->nome,
                'sigla' => $disciplina->sigla ?? '',
                'tdp_id' => (string) $turmaDisciplinaProfessor->getKey(),
            ];
        }

        return $disciplinas;
    }

    /**
     * Prepara e ordena os alunos e as respetivas notas do período.
     *
     * @return list<array{name: string, result: string, grades: array<string, float|null>}>
     */
    public function prepararAlunos(Turma $turma, int $periodo): array
    {
        $turmaAlunos = $this->consultarTurmaAlunos($turma, $periodo);
        $resultadosPorAluno = $this->obterResultadosPorAluno($turma, $periodo, $turmaAlunos->count());
        $alunos = [];

        foreach ($turmaAlunos as $turmaAluno) {
            $alunos[] = $this->mapearAluno($turmaAluno, $resultadosPorAluno);
        }

        // Remove acentos antes da ordenação natural para manter a ordem alfabética.
        usort($alunos, static fn (array $alunoA, array $alunoB): int => strnatcasecmp(
            Str::ascii($alunoA['name'], 'pt'),
            Str::ascii($alunoB['name'], 'pt'),
        ));

        return $alunos;
    }

    /**
     * Consulta os alunos activos e carrega os dados exigidos pela pauta.
     *
     * @return EloquentCollection<int, TurmaAluno>
     */
    private function consultarTurmaAlunos(Turma $turma, int $periodo): EloquentCollection
    {
        /** @var EloquentCollection<int, TurmaAluno> $turmaAlunos */
        $turmaAlunos = TurmaAluno::query()
            ->with([
                'aluno.inscricao.candidato:id,nome',
                'notas' => fn ($query) => $query->where('periodo', $periodo),
            ])
            ->where('turma_id', $turma->getKey())
            ->whereHas('aluno', fn ($query) => $query->inscricaoNaoCancelada())
            ->where('activo', true)
            ->where('situacao', 'activo')
            ->orderBy('created_at')
            ->get();

        return $turmaAlunos;
    }

    /**
     * Obtém os resultados trimestrais indexados pelo identificador do aluno.
     *
     * @return array<string, string>
     */
    private function obterResultadosPorAluno(Turma $turma, int $periodo, int $totalAlunos): array
    {
        /** @var array{alunos: LengthAwarePaginator} $pauta */
        $pauta = $this->pautaService->gerarPauta($turma, $periodo, max(1, $totalAlunos));
        $resultadosPorAluno = [];

        foreach ($pauta['alunos']->items() as $alunoPauta) {
            /** @var array{aluno_id: int|string|null, resultado: mixed} $alunoPauta */
            $resultadosPorAluno[(string) $alunoPauta['aluno_id']] = (string) $alunoPauta['resultado'];
        }

        return $resultadosPorAluno;
    }

    /**
     * Converte um aluno e as notas carregadas no formato do export trimestral.
     *
     * @param  array<string, string>  $resultadosPorAluno
     * @return array{name: string, result: string, grades: array<string, float|null>}
     */
    private function mapearAluno(TurmaAluno $turmaAluno, array $resultadosPorAluno): array
    {
        return [
            'name' => $turmaAluno->aluno?->inscricao?->candidato?->nome ?? '',
            'result' => $resultadosPorAluno[(string) $turmaAluno->aluno?->getKey()] ?? '',
            'grades' => $turmaAluno->notas
                ->keyBy('turma_disciplina_professor_id')
                ->map(fn ($nota): ?float => $nota->media_trimestral)
                ->all(),
        ];
    }
}
