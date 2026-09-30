<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Pauta\PreparePautaIndex;
use App\Actions\Tenant\Pauta\PreparePautaShow;
use App\Http\Controllers\Controller;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Apresenta a listagem de turmas e as pautas.
 */
class PautaController extends Controller
{
    public function __construct(
        private readonly PreparePautaIndex $preparePautaIndex,
        private readonly PreparePautaShow $preparePautaShow,
    ) {}

    /**
     * Mostra as turmas disponíveis para consulta de pautas.
     */
    public function index(Request $request): Response
    {
        $this->authorize('pauta.viewAny', CursoTutelado::class);

        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        return Inertia::render(
            'tenant/pautas/index',
            $this->preparePautaIndex->handle($user, $request),
        );
    }

    /**
     * Mostra a pauta da turma seleccionada.
     */
    public function pauta(string $turma, Request $request): Response
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        return Inertia::render(
            'tenant/pautas/show',
            $this->preparePautaShow->handle($user, $turma, $request),
        );
    }
}
