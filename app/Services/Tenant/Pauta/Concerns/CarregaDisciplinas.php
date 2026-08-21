<?php

namespace App\Services\Tenant\Pauta\Concerns;

use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaDisciplinaProfessor;
use Illuminate\Support\Collection;

trait CarregaDisciplinas
{
    private function carregarDisciplinas(Turma $turma): Collection
    {
        return TurmaDisciplinaProfessor::with('classeTurnoDisciplina.disciplina')
            ->where('turma_id', $turma->id)
            ->get()
            ->unique('classe_turno_disciplina_id')
            ->map(fn ($tdp) => [
                'id' => $tdp->classeTurnoDisciplina?->disciplina?->id,
                'sigla' => $tdp->classeTurnoDisciplina?->disciplina?->sigla,
                'nome' => $tdp->classeTurnoDisciplina?->disciplina?->nome,
                'tdp_id' => $tdp->id,
            ])
            ->filter(fn ($d) => $d['id'] !== null)
            ->values();
    }
}
