<?php

namespace App\Services;

use App\Models\Aluno;
use App\Models\CursoClasse;

class HistoricoVerificacaoService
{
    public function classesPendentes(Aluno $aluno): array
    {
        $inscricao = $aluno->inscricao?->loadMissing([
            'cursoClasseTurno.cursoClasse.classe',
        ]);

        if (! $inscricao) {
            return [];
        }

        $cursoClasseActual = $inscricao->cursoClasseTurno?->cursoClasse;

        if (! $cursoClasseActual) {
            return [];
        }

        $ordemActual = $cursoClasseActual->classe->ordem;
        $cursoTuteladoId = $cursoClasseActual->curso_tutelado_id;

        $classesAnteriores = CursoClasse::with('classe')
            ->where('curso_tutelado_id', $cursoTuteladoId)
            ->whereHas('classe', fn ($q) => $q->where('ordem', '<', $ordemActual))
            ->get();

        if ($classesAnteriores->isEmpty()) {
            return [];
        }

        return $classesAnteriores
            ->map(fn ($cc) => [
                'curso_classe_id' => $cc->id,
                'classe' => $cc->classe->nome,
                'ordem' => $cc->classe->ordem,
            ])
            ->sortBy('ordem')
            ->values()
            ->toArray();
    }
}
