<?php

namespace App\Services\Tenant\Pauta;

use App\Models\Tenant\Turma;

class ResolvePautaTemplatePath
{
    public function resolveFinal(Turma $turma): ?string
    {
        return $this->resolve($turma, 'final.xlsx');
    }

    public function resolveTrimestral(Turma $turma): ?string
    {
        return $this->resolve($turma, 'trimestral.xlsx');
    }

    private function resolve(Turma $turma, string $filename): ?string
    {
        $turma->loadMissing([
            'cursoClasseTurno.turno',
            'cursoClasseTurno.cursoClasse.classe',
        ]);

        $classe = $turma->cursoClasseTurno?->cursoClasse?->classe;

        $turno = $turma->cursoClasseTurno?->turno;

        preg_match('/\d+/', (string) $classe?->nome, $classeNumero);

        $classeSegment = isset($classeNumero[0])
            ? "{$classeNumero[0]}-classe"
            : (string) str($classe?->nome ?? '')->slug();

        $turnoSegment = (string) str($turno?->nome ?? '')->slug();

        $templatePath = resource_path(
            "templates/pautas/classes/{$classeSegment}/turnos/{$turnoSegment}/{$filename}",
        );

        return is_file($templatePath) ? $templatePath : null;
    }
}
