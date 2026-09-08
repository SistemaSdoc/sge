<?php

namespace App\Services\Tenant\GrupoPap;

use App\Http\Controllers\Tenant\GrupoPapController;
use App\Models\Tenant\User;

class GrupoPapNavigationService
{
    /**
     * @return array{title: string, href: string, visible: bool}
     */
    public function resolve(?User $user): array
    {
        $indexUrl = action([GrupoPapController::class, 'index']);

        if (! $user?->hasRole('Aluno')) {
            return [
                'title' => 'Grupos PAP',
                'href' => $indexUrl,
                'visible' => true,
            ];
        }

        $grupoPap = $user->aluno?->grupoPap()
            ->with('turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao')
            ->first();

        if (! $grupoPap) {
            return [
                'title' => 'Meu Grupo PAP',
                'href' => $indexUrl,
                'visible' => false,
            ];
        }

        $turma = $grupoPap->turma;
        $cursoClasseTurno = $turma?->cursoClasseTurno;
        $cursoClasse = $cursoClasseTurno?->cursoClasse;
        $cursoTutelado = $cursoClasse?->cursoTutelado;
        $instituicao = $cursoTutelado?->instituicaoCurso?->instituicao;

        if (! $turma || ! $cursoClasseTurno || ! $cursoClasse || ! $cursoTutelado || ! $instituicao) {
            return [
                'title' => 'Meu Grupo PAP',
                'href' => $indexUrl,
                'visible' => false,
            ];
        }

        return [
            'title' => 'Meu Grupo PAP',
            'href' => action([GrupoPapController::class, 'show'], [
                'instituicao' => $instituicao->getKey(),
                'cursoTutelado' => $cursoTutelado->getKey(),
                'cursoClasse' => $cursoClasse->getKey(),
                'cursoClasseTurno' => $cursoClasseTurno->getKey(),
                'turma' => $turma->getKey(),
                'grupoPap' => $grupoPap->getKey(),
            ]),
            'visible' => true,
        ];
    }
}
