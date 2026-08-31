<?php

namespace App\Actions\Tenant\CursoTutelado;

use App\Models\Tenant\CursoTutelado;
use App\Services\Tenant\Tutela\TutelaService;
use Illuminate\Support\Facades\DB;

/**
 * Remove um curso tutelado depois de validar as suas dependências.
 */
class DeleteCursoTutelado
{
    public function __construct(private readonly TutelaService $tutelaService) {}

    /**
     * Encerra a tutela e remove o curso sem turmas associadas.
     */
    public function handle(CursoTutelado $cursoTutelado): void
    {
        if ($cursoTutelado->cursoClasses()->whereHas('turnos')->exists()) {
            abort(422, 'Não é possível remover um curso que tem turmas associadas.');
        }

        $this->tutelaService->encerrarTutela($cursoTutelado);

        DB::transaction(function () use ($cursoTutelado): void {
            $cursoTutelado->delete();
        });
    }
}
