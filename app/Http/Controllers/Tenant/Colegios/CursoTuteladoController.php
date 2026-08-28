<?php

namespace App\Http\Controllers\Tenant\Colegios;

use App\Actions\Tenant\Colegios\CursoTutelado\ShowCursoTutelado;
use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Orquestra a consulta de cursos tutelados pelo módulo dos colégios.
 */
class CursoTuteladoController extends Controller
{
    public function __construct(private readonly ShowCursoTutelado $showCursoTutelado) {}

    /**
     * Apresenta um curso do colégio ao seu instituto tutor.
     */
    public function show(string $colegio, string $cursoTutelado)
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        return Inertia::render(
            'tenant/colegio/cursos-tutelados/show',
            $this->showCursoTutelado->handle($user, $colegio, $cursoTutelado),
        );
    }
}
