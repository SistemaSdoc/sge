<?php

namespace App\Services\Tenant\Pauta\Exports\Final;

use App\Models\Tenant\Aluno;
use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaAluno;
use App\Services\Tenant\Pauta\NotasClasseAnterior;
use App\Services\Tenant\Pauta\PautaService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Consulta e prepara disciplinas e alunos para o template final.
 */
final class PautaFinalTemplateDataProvider
{
    /**
     * Injeta os serviços de geração da pauta e consulta do histórico escolar.
     */
    public function __construct(
        private readonly PautaService $pautaService,
        private readonly NotasClasseAnterior $notasClasseAnterior,
        private readonly PautaFinalStudentMapper $studentMapper,
    ) {}

    /**
     * Obtém os dados finais das disciplinas e dos alunos da turma.
     *
     * @return array{disciplines: list<array{id: string, name: string, sigla: string}>, students: list<array<string, mixed>>}
     */
    public function preparar(Turma $turma): array
    {
        $totalAlunos = TurmaAluno::query()
            ->where('turma_id', $turma->getKey())
            ->where('activo', true)
            ->count();

        /** @var array{disciplinas: Collection<int, array<string, mixed>>, alunos: LengthAwarePaginator} $pauta */
        $pauta = $this->pautaService->gerarPauta($turma, 'final', max(1, $totalAlunos));

        $disciplinas = $this->prepararDisciplinas($pauta['disciplinas']);

        /** @var Collection<int, array<string, mixed>> $alunosPauta */
        $alunosPauta = $pauta['alunos']->getCollection();

        $alunoIds = $this->obterIdsAlunos($alunosPauta);

        $numerosProcesso = Aluno::query()
            ->whereIn('id', $alunoIds)
            ->pluck('numero_processo', 'id');

        $historicoNotas = $this->notasClasseAnterior->obterTodas($turma, $alunoIds);

        $ordemClasse = (int) ($turma->cursoClasseTurno?->cursoClasse?->classe?->ordem ?? 0);

        return [
            'disciplines' => $disciplinas,
            'students' => $this->studentMapper->mapearAlunos(
                $alunosPauta,
                $disciplinas,
                $historicoNotas,
                $numerosProcesso,
                $ordemClasse,
            ),
        ];
    }

    /**
     * Converte as disciplinas calculadas pelo serviço para o formato do export.
     *
     * @param  Collection<int, array<string, mixed>>  $disciplinasPauta
     * @return list<array{id: string, name: string, sigla: string}>
     */
    private function prepararDisciplinas(Collection $disciplinasPauta): array
    {
        $disciplinas = [];

        foreach ($disciplinasPauta as $disciplina) {
            $disciplinas[] = [
                'id' => (string) ($disciplina['id'] ?? ''),
                'name' => (string) ($disciplina['nome'] ?? ''),
                'sigla' => (string) ($disciplina['sigla'] ?? ''),
            ];
        }

        return $disciplinas;
    }

    /**
     * Extrai os identificadores dos alunos da página calculada pelo serviço.
     *
     * @param  Collection<int, array<string, mixed>>  $alunosPauta
     * @return list<string>
     */
    private function obterIdsAlunos(Collection $alunosPauta): array
    {
        $alunoIds = [];

        foreach ($alunosPauta as $aluno) {
            $alunoIds[] = (string) ($aluno['aluno_id'] ?? '');
        }

        return $alunoIds;
    }
}
