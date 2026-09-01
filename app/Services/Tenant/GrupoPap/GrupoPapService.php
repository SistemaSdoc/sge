<?php

namespace App\Services\Tenant\GrupoPap;

use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Turma;
use App\Services\Tenant\GrupoPap\GrupoPapViewService;

class GrupoPapService
{

    /**
     * Devolve as classes de 13.º ano do curso tutelado indicado.
     * Usa distinct na query para evitar duplicados, sem depender de uniqueBy.
     */
    public function classes(string $cursoTuteladoId): \Illuminate\Support\Collection
    {
        return CursoClasse::query()
            ->where('curso_tutelado_id', $cursoTuteladoId)
            ->whereHas('classe', fn($q) => $q->where('nome', 'LIKE', '13%'))
            ->with('classe:id,nome')
            ->orderBy('id')
            ->get()
            ->map(fn($cc) => [
                'id' => $cc->id,        /* ← id do CursoClasse, não da Classe */
                'nome' => $cc->classe?->nome ?? 'Classe',
            ])
            ->unique('nome')              /* ← agrupa pelo nome para não repetir "13ª" */
            ->values();
    }
    /**
     * Devolve os turnos disponíveis para a classe indicada.
     */
    public function turnos(string $cursoClasseId): \Illuminate\Support\Collection
    {
        return CursoClasseTurno::query()
            ->where('curso_classe_id', $cursoClasseId)
            ->with('turno:id,nome')
            ->get()
            ->map(fn($cct) => [
                'id' => $cct->id,
                'nome' => $cct->turno->nome,
            ])
            ->values();
    }

    /**
     * Devolve as turmas do turno indicado, ordenadas por nome.
     */
    public function turmas(string $cursoClasseTurnoId): \Illuminate\Support\Collection
    {
        return Turma::query()
            ->where('curso_classe_turno_id', $cursoClasseTurnoId)
            ->orderBy('nome')
            ->get(['id', 'nome'])
            ->values();
    }

    /**
     * Devolve professores e alunos disponíveis para o formulário de criação.
     * Retorna arrays vazios se a turma não for de 13.º ano.
     */
    public function formOptions(string $cursoTuteladoId, string $turmaId): array
    {
        $cursoTutelado = CursoTutelado::query()->find($cursoTuteladoId);
        $turma = Turma::query()->find($turmaId);

        if (!$cursoTutelado || !$turma) {
            return ['professores' => [], 'alunos' => []];
        }

        $classeNome = $turma->cursoClasseTurno?->cursoClasse?->classe?->nome ?? '';

        /* Só turmas de 13.º ano podem ter grupo PAP */
        if (!str_contains(strtolower($classeNome), '13')) {
            return ['professores' => [], 'alunos' => []];
        }

        $options = app(GrupoPapViewService::class)->createOptions($cursoTutelado, $turma);

        return [
            'professores' => $options['professores']
                ->map(fn($p) => [
                    'id' => $p->id,
                    'nome' => $p->user?->nome ?? 'Sem nome',
                ])
                ->values(),
            'alunos' => $options['alunos'],
        ];
    }
}