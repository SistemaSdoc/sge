<?php

namespace App\Services\Tenant\Pauta\Exports\Final;

use App\Models\Central\Disciplina as CentralDisciplina;
use App\Services\Tenant\Pauta\PautaFinalDisciplineMapper;
use Illuminate\Support\Collection;

/**
 * Mapeia alunos, notas actuais e histórico para o template final.
 */
final class PautaFinalStudentMapper
{
    /**
     * Injeta o mapper que resolve nomes e siglas de disciplinas.
     */
    public function __construct(private readonly PautaFinalDisciplineMapper $disciplineMapper) {}

    /**
     * Prepara todos os alunos para o export da pauta final.
     *
     * @param  Collection<int, array<string, mixed>>  $alunosPauta
     * @param  list<array{id: string, name: string, sigla: string}>  $disciplinas
     * @param  array{disciplinas: array<string, array{id: string}>, alunos: array<string, array<int, array<string, float>>>}  $historicoNotas
     * @param  Collection<string, int|string|null>  $numerosProcesso
     * @return list<array{number: int|string|null, name: string, grades: array<string, array<string, mixed>>, previous_grades: array<string, float>, result: string, disciplines_recurso: list<string>}>
     */
    public function mapearAlunos(
        Collection $alunosPauta,
        array $disciplinas,
        array $historicoNotas,
        Collection $numerosProcesso,
        int $ordemClasse,
    ): array {
        $disciplinasActuais = $this->mapearDisciplinasAtuais($disciplinas);

        $disciplinasHistoricas = $this->mapearDisciplinasHistoricas($historicoNotas);

        $alunos = [];

        foreach ($alunosPauta as $aluno) {
            $alunos[] = $this->mapearAluno(
                $aluno,
                $disciplinasActuais,
                $disciplinasHistoricas,
                $historicoNotas,
                $numerosProcesso,
                $ordemClasse,
            );
        }

        return $alunos;
    }

    /**
     * Resolve as chaves de template correspondentes às disciplinas actuais.
     *
     * @param  list<array{id: string, name: string, sigla: string}>  $disciplinas
     * @return array<string, string|null>
     */
    private function mapearDisciplinasAtuais(array $disciplinas): array
    {
        $disciplinasActuais = [];

        foreach ($disciplinas as $disciplina) {
            $disciplinasActuais[$disciplina['id']] = $this->disciplineMapper->resolve(
                $disciplina['sigla'],
                $disciplina['name'],
            );
        }

        return $disciplinasActuais;
    }

    /**
     * Resolve as chaves de template para as disciplinas das classes anteriores.
     *
     * @param  array{disciplinas: array<string, array{id: string}>, alunos: array<string, array<int, array<string, float>>>}  $historicoNotas
     * @return array<string, string>
     */
    private function mapearDisciplinasHistoricas(array $historicoNotas): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, CentralDisciplina> $disciplinasEncontradas */
        $disciplinasEncontradas = CentralDisciplina::query()
            ->whereIn('id', array_keys($historicoNotas['disciplinas']))
            ->get(['id', 'sigla', 'nome']);

        $disciplinasHistoricas = [];

        foreach ($disciplinasEncontradas as $disciplina) {
            $chave = $this->disciplineMapper->resolve(
                (string) ($disciplina->getAttribute('sigla') ?? ''),
                (string) ($disciplina->getAttribute('nome') ?? ''),
            );

            if ($chave !== null) {
                $disciplinasHistoricas[(string) $disciplina->getKey()] = $chave;
            }
        }

        return $disciplinasHistoricas;
    }

    /**
     * Combina notas actuais e históricas para um aluno.
     *
     * @param  array<string, mixed>  $aluno
     * @param  array<string, string|null>  $disciplinasActuais
     * @param  array<string, string>  $disciplinasHistoricas
     * @param  array{disciplinas: array<string, array{id: string}>, alunos: array<string, array<int, array<string, float>>>}  $historicoNotas
     * @param  Collection<string, int|string|null>  $numerosProcesso
     * @return array{number: int|string|null, name: string, grades: array<string, array<string, mixed>>, previous_grades: array<string, float>, result: string, disciplines_recurso: list<string>}
     */
    private function mapearAluno(
        array $aluno,
        array $disciplinasActuais,
        array $disciplinasHistoricas,
        array $historicoNotas,
        Collection $numerosProcesso,
        int $ordemClasse,
    ): array {
        $alunoId = (string) ($aluno['aluno_id'] ?? '');
        $notasDoAluno = $historicoNotas['alunos'][$alunoId] ?? [];
        $notasHistoricas = [];

        foreach ($disciplinasHistoricas as $disciplinaId => $chave) {
            for ($ordemHistorica = 10; $ordemHistorica < $ordemClasse; $ordemHistorica++) {
                $nota = $notasDoAluno[$ordemHistorica][$disciplinaId] ?? null;

                if ($nota === null) {
                    continue;
                }

                $notasHistoricas[$chave.'-'.$ordemHistorica] = $nota;

                // Mantém a chave histórica legada consumida pelo writer da 12.ª.
                if ($ordemHistorica === $ordemClasse - 2) {
                    $notasHistoricas[$chave] = $nota;
                }
            }
        }

        foreach ($disciplinasActuais as $disciplinaId => $chave) {
            if ($chave === null) {
                continue;
            }

            $notaAnterior = $notasHistoricas[$chave.'-11'] ?? null;

            if ($notaAnterior !== null) {
                $notasHistoricas[$disciplinaId] = $notaAnterior;
            }
        }

        $notasActuais = $this->mapearNotasAtuais($aluno['notas'] ?? [], $disciplinasActuais);
        $disciplinasRecurso = [];

        foreach ($aluno['disciplinas_recurso'] ?? [] as $disciplinaId) {
            $disciplinasRecurso[] = (string) $disciplinaId;
        }

        return [
            'number' => $numerosProcesso->get($alunoId),
            'name' => (string) ($aluno['nome'] ?? ''),
            'grades' => $notasActuais,
            'previous_grades' => $notasHistoricas,
            'result' => (string) ($aluno['resultado'] ?? 'incompleto'),
            'disciplines_recurso' => $disciplinasRecurso,
        ];
    }

    /**
     * Mapeia as notas actuais pelas chaves usadas no template final.
     *
     * @param  iterable<string, array<string, mixed>>  $notas
     * @param  array<string, string|null>  $disciplinasActuais
     * @return array<string, array<string, mixed>>
     */
    private function mapearNotasAtuais(iterable $notas, array $disciplinasActuais): array
    {
        $notasMapeadas = [];

        foreach ($notas as $disciplinaId => $nota) {
            $chave = $disciplinasActuais[(string) $disciplinaId] ?? null;

            if ($chave === null) {
                continue;
            }

            $valores = [
                'mt1' => $nota['t1'] ?? null,
                'mt2' => $nota['t2'] ?? null,
                'mt3' => $nota['t3'] ?? null,
                'mf' => $nota['mf'] ?? null,
                'faltas' => (int) ($nota['total_faltas'] ?? 0),
            ];

            $notasMapeadas[(string) $disciplinaId] = $valores;
            $notasMapeadas[$chave] = $valores;
        }

        return $notasMapeadas;
    }
}
