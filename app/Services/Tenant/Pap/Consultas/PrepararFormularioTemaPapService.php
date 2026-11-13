<?php

namespace App\Services\Tenant\Pap\Consultas;

use App\Models\Central\AnoLectivo;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class PrepararFormularioTemaPapService
{
    /**
     * Lista os professores disponíveis para definir uma proposta de tema.
     *
     * @return Collection<int, Professor>
     */
    public function professoresParaTema(string $cursoTuteladoId): Collection
    {
        return Professor::query()
            ->whereHas('cursosTutelados', fn ($query) => $query
                ->where('curso_tutelado_id', $cursoTuteladoId)
                ->where('opap', true))
            ->with('user:id,nome')
            ->get();
    }

    /**
     * Lista as classes usadas no selector de tema.
     *
     * Este filtro mantém o critério exacto da consulta original de temas.
     */
    public function classesParaTema(string $cursoTuteladoId): Collection
    {
        /** @var EloquentCollection<int, CursoClasse> $classes */
        $classes = CursoClasse::query()
            ->where('curso_tutelado_id', $cursoTuteladoId)
            ->whereHas('classe', fn ($query) => $query->where('nome', '13ª'))
            ->with('classe:id,nome')
            ->orderBy('id')
            ->get();

        return $classes->map(fn (CursoClasse $cursoClasse) => [
            'id' => $cursoClasse->id,
            'nome' => $cursoClasse->classe?->nome ?? $cursoClasse->nome,
        ]);
    }

    /**
     * Lista os turnos usados no selector de tema.
     */
    public function turnosParaTema(string $cursoClasseId): Collection
    {
        return CursoClasseTurno::query()
            ->where('curso_classe_id', $cursoClasseId)
            ->with('turno:id,nome')
            ->get()
            ->map(fn ($cursoClasseTurno) => [
                'id' => $cursoClasseTurno->id,
                'nome' => $cursoClasseTurno->turno->nome,
            ]);
    }

    /**
     * Lista as turmas usadas no selector de tema.
     */
    public function turmasParaTema(string $cursoClasseTurnoId): Collection
    {
        return Turma::query()
            ->where('curso_classe_turno_id', $cursoClasseTurnoId)
            ->orderBy('nome')
            ->get(['id', 'nome']);
    }

    /**
     * Lista os anos lectivos disponíveis.
     *
     * @return Collection<int, AnoLectivo>
     */
    public function anosLectivos(): Collection
    {
        return AnoLectivo::all();
    }
}
