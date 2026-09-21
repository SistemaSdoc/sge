<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Turno\CreateTurno;
use App\Actions\Tenant\Turno\DeleteTurno;
use App\Actions\Tenant\Turno\UpdateTurno;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Turno\StoreTurnoRequest;
use App\Http\Requests\Tenant\Turno\UpdateTurnoRequest;
use App\Models\Tenant\Turno;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TurnoController extends Controller
{
    public function __construct(
        private readonly CreateTurno $createTurno,
        private readonly UpdateTurno $updateTurno,
        private readonly DeleteTurno $deleteTurno,
    ) {
        $this->authorizeResource(Turno::class, 'turno');
    }

    /**
     * Mostra a lista de turnos.
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $turnos = Turno::select(['id', 'nome', 'created_at'])
            ->orderBy('nome', 'asc')
            ->paginate(10)
            ->through(function (Turno $turno) use ($user) {
                return [
                    'id' => $turno->id,
                    'nome' => $turno->nome,
                    'can' => [
                        'view' => $user->can('view', $turno),
                        'edit' => $user->can('update', $turno),
                        'delete' => $user->can('delete', $turno),
                    ],
                ];
            });

        return Inertia::render('tenant/turnos/index', [
            'turnos' => $turnos,
            'can' => [
                'create' => $user->can('create', Turno::class),
            ],
        ]);
    }

    /**
     * Mostra o formulário para criar um novo turno.
     */
    public function create()
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        return Inertia::render('tenant/turnos/create', [
            'can' => [
                'create' => $user->can('create', Turno::class),
            ],
        ]);
    }

    /**
     * Guarda um novo turno no tenant actual.
     */
    public function store(StoreTurnoRequest $request)
    {
        $this->createTurno->handle($request->validated());

        return to_route('tenant.dashboard.turnos.index')->with('toast', [
            'type' => 'success',
            'message' => 'Turno criado com sucesso!',
        ]);
    }

    /**
     * Mostra os dados de um turno específico.
     */
    public function show(Turno $turno)
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        return Inertia::render('tenant/turnos/show', [
            'turno' => $turno,
            'can' => [
                'view' => $user->can('view', $turno),
                'edit' => $user->can('update', $turno),
                'delete' => $user->can('delete', $turno),
            ],
        ]);
    }

    /**
     * Mostra o formulário para editar um turno específico.
     */
    public function edit(Turno $turno)
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        return Inertia::render('tenant/turnos/edit', [
            'turno' => $turno,
            'can' => [
                'edit' => $user->can('update', $turno),
            ],
        ]);
    }

    /**
     * Actualiza um turno específico.
     */
    public function update(UpdateTurnoRequest $request, Turno $turno)
    {
        $this->updateTurno->handle($turno, $request->validated());

        return to_route('tenant.dashboard.turnos.index')->with('toast', [
            'type' => 'success',
            'message' => 'Turno atualizado com sucesso!',
        ]);
    }

    /**
     * Remove um turno específico.
     */
    public function destroy(Turno $turno)
    {
        $this->deleteTurno->handle($turno);

        return to_route('tenant.dashboard.turnos.index')->with('toast', [
            'type' => 'success',
            'message' => 'Turno excluído com sucesso!',
        ]);
    }
}
