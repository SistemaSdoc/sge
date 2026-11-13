<?php

namespace App\Services\Tenant\Pap\Consultas;

use App\Models\Tenant\Aluno;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\ElementoGrupoPap;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use Illuminate\Support\Collection;
use Illuminate\Support\Collection as SupportCollection;

class PrepararFormularioGrupoPapService
{
    /**
     * Lista as classes de 13.º ano do curso tutelado.
     */
    public function classesParaGrupo(string $cursoTuteladoId): SupportCollection
    {
        return CursoClasse::query()
            ->where('curso_tutelado_id', $cursoTuteladoId)
            ->whereHas('classe', fn ($query) => $query->where('nome', 'LIKE', '13%'))
            ->with('classe:id,nome')
            ->orderBy('id')
            ->get()
            ->map(fn ($cursoClasse) => [
                'id' => $cursoClasse->id,
                'nome' => $cursoClasse->classe?->nome ?? 'Classe',
            ])
            ->unique('nome')
            ->values();
    }

    /**
     * Lista os turnos disponíveis para uma classe.
     */
    public function turnosParaGrupo(string $cursoClasseId): SupportCollection
    {
        return CursoClasseTurno::query()
            ->where('curso_classe_id', $cursoClasseId)
            ->with('turno:id,nome')
            ->get()
            ->map(fn ($cursoClasseTurno) => [
                'id' => $cursoClasseTurno->id,
                'nome' => $cursoClasseTurno->turno->nome,
            ])
            ->values();
    }

    /**
     * Lista as turmas do turno indicado e, opcionalmente, do ano lectivo.
     */
    public function turmasParaGrupo(string $cursoClasseTurnoId, ?string $anoLectivoId = null): SupportCollection
    {
        return Turma::query()
            ->where('curso_classe_turno_id', $cursoClasseTurnoId)
            ->when($anoLectivoId, fn ($query) => $query->where('ano_lectivo_id', $anoLectivoId))
            ->orderBy('nome')
            ->get(['id', 'nome'])
            ->values();
    }

    /**
     * Prepara professores e alunos para o formulário independente de grupo PAP.
     *
     * @return array{professores: array|SupportCollection, alunos: array|SupportCollection}
     */
    public function opcoesFormularioIndependente(string $cursoTuteladoId, string $turmaId): array
    {
        $cursoTutelado = CursoTutelado::query()->find($cursoTuteladoId);
        $turma = Turma::query()->find($turmaId);

        if (! $cursoTutelado || ! $turma) {
            return ['professores' => [], 'alunos' => []];
        }

        $classeNome = $turma->cursoClasseTurno?->cursoClasse?->classe?->nome ?? '';

        if (! str_contains(strtolower($classeNome), '13')) {
            return ['professores' => [], 'alunos' => []];
        }

        $opcoes = $this->opcoesCriacao($cursoTutelado, $turma);

        return [
            'professores' => $opcoes['professores']
                ->map(fn ($professor) => [
                    'id' => $professor->id,
                    'nome' => $professor->user?->nome ?? 'Sem nome',
                ])
                ->values(),
            'alunos' => $opcoes['alunos'],
        ];
    }

    /**
     * Lista os alunos disponíveis para uma turma.
     *
     * @return SupportCollection<int, array{id: mixed, nome: string}>
     */
    public function alunosDisponiveisParaTurma(Turma $turma): SupportCollection
    {
        return $this->alunosDisponiveis($turma);
    }

    /**
     * Prepara as opções para criar um grupo PAP numa turma.
     *
     * @return array{professores: Collection, alunos: SupportCollection}
     */
    public function opcoesCriacao(CursoTutelado $cursoTutelado, Turma $turma): array
    {
        return [
            'professores' => Professor::query()
                ->whereHas('cursosTutelados', fn ($query) => $query
                    ->where('curso_tutelado_id', $cursoTutelado->getKey())
                    ->where('tipo', 'principal'))
                ->with('user:id,nome')
                ->get(),
            'alunos' => $this->alunosDisponiveis($turma),
        ];
    }

    /**
     * Prepara as opções de edição, incluindo os alunos que já pertencem ao grupo.
     *
     * @return array{professores: Collection, alunos: SupportCollection}
     */
    public function opcoesEdicao(
        CursoTutelado $cursoTutelado,
        Turma $turma,
        GrupoPap $grupoPap,
    ): array {
        return [
            'professores' => Professor::query()
                ->where(function ($query) use ($cursoTutelado, $grupoPap): void {
                    $query->whereHas('cursosTutelados', fn ($courseQuery) => $courseQuery
                        ->where('curso_tutelado_id', $cursoTutelado->getKey())
                        ->where('opap', true))
                        ->orWhereKey($grupoPap->professor_tutor_id);
                })
                ->with('user:id,nome')
                ->get(),
            'alunos' => $turma->alunos()
                ->where(function ($query) use ($grupoPap): void {
                    $query->whereDoesntHave('grupoPap')
                        ->orWhereHas('grupoPap', fn ($grupoQuery) => $grupoQuery->whereKey($grupoPap->getKey()));
                })
                ->with('inscricao.candidato:id,nome')
                ->get()
                ->map(fn (Aluno $aluno): array => [
                    'id' => $aluno->id,
                    'nome' => $aluno->inscricao?->candidato?->nome ?? 'Sem nome',
                ]),
        ];
    }

    /**
     * Lista os alunos activos da turma que ainda não pertencem a um grupo PAP.
     *
     * @return SupportCollection<int, array{id: mixed, nome: string}>
     */
    private function alunosDisponiveis(Turma $turma): SupportCollection
    {
        $alunosEmGrupo = ElementoGrupoPap::query()->pluck('aluno_id');

        return Aluno::query()
            ->whereNotIn('id', $alunosEmGrupo)
            ->whereHas('turmas', fn ($query) => $query
                ->where('turmas.id', $turma->getKey())
                ->where('turma_aluno.activo', true))
            ->with('inscricao.candidato:id,nome')
            ->get()
            ->sortBy(fn (Aluno $aluno) => $aluno->inscricao?->candidato?->nome)
            ->values()
            ->map(fn (Aluno $aluno): array => [
                'id' => $aluno->id,
                'nome' => $aluno->inscricao?->candidato?->nome ?? 'Sem nome',
            ]);
    }
}
