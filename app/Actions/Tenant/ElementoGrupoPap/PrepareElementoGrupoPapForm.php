<?php

namespace App\Actions\Tenant\ElementoGrupoPap;

use App\Models\Tenant\Aluno;
use App\Models\Tenant\ElementoGrupoPap;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Turma;
use Illuminate\Support\Collection;

class PrepareElementoGrupoPapForm
{
    /**
     * Obtém os alunos activos da turma que ainda não pertencem a um grupo PAP.
     *
     * @return Collection<int, array{id: string, nome: string}>
     */
    public function handle(Turma $turma, GrupoPap $grupoPap): Collection
    {
        $alunosEmGrupo = ElementoGrupoPap::query()
            ->where('grupo_pap_id', $grupoPap->getKey())
            ->pluck('aluno_id');

        return Aluno::with('inscricao.candidato:id,nome')
            ->whereNotIn('id', $alunosEmGrupo)
            ->whereHas(
                'turmas',
                fn ($query) => $query
                    ->where('turmas.id', $turma->getKey())
                    ->where('turma_aluno.activo', true)
            )
            ->get()
            ->map(fn (Aluno $aluno): array => [
                'id' => $aluno->getKey(),
                'nome' => $aluno->inscricao?->candidato?->nome ?? 'Sem nome',
            ])
            ->values();
    }
}
