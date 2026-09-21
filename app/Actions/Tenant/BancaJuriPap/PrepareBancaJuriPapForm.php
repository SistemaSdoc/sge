<?php

namespace App\Actions\Tenant\BancaJuriPap;

use App\Models\Central\AnoLectivo;
use App\Models\Tenant\BancaJuriPap;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Turma;

class PrepareBancaJuriPapForm
{
    /**
     * Prepara os dados necessários para os formulários de criação e edição da banca.
     *
     * @return array<string, mixed>
     */
    public function handle(
        Turma $turma,
        CursoTutelado $cursoTutelado,
        GrupoPap $grupoPap,
        ?BancaJuriPap $bancaJuriPap = null,
    ): array {
        $juradosNaBanca = $grupoPap->jurados()
            ->when($bancaJuriPap, fn ($query) => $query->whereKeyNot($bancaJuriPap->getKey()))
            ->pluck('professor_id');

        $professores = $cursoTutelado->professores()
            ->whereNotIn('professores.id', $juradosNaBanca)
            ->wherePivot('tipo', 'principal')
            ->with('user:id,nome')
            ->get()
            ->map(fn ($professor): array => [
                'id' => $professor->id,
                'nome' => $professor->user?->nome ?? 'Sem nome',
            ])->values();

        return [
            'anoLectivoId' => $turma->ano_lectivo_id,
            'anosLectivos' => AnoLectivo::all(),
            'professores' => $professores,
            'funcoes' => ['Presidente', 'Vogal 1', 'Vogal 2'],
        ];
    }
}
