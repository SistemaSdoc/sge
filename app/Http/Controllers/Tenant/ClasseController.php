<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Classe\CreateClasse;
use App\Actions\Tenant\Classe\DeleteClasse;
use App\Actions\Tenant\Classe\UpdateClasse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Classe\StoreClasseRequest;
use App\Http\Requests\Tenant\Classe\UpdateClasseRequest;
use App\Models\Tenant\Classe;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ClasseController extends Controller
{
    public function __construct(
        private readonly CreateClasse $createClasse,
        private readonly UpdateClasse $updateClasse,
        private readonly DeleteClasse $deleteClasse,
    ) {
        $this->authorizeResource(Classe::class, 'classe');
    }

    /**
     * Mostra a lista de classes.
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $classes = Classe::select(['id', 'nome', 'nivel_ensino', 'created_at'])
            ->orderBy('nome', 'asc')
            ->paginate(10)
            ->through(function (Classe $classe) use ($user) {
                return [
                    'id' => $classe->id,
                    'nome' => $classe->nome,
                    'nivel_ensino' => $classe->nivel_ensino,
                    'can' => [
                        'view' => $user->can('view', $classe),
                        'edit' => $user->can('update', $classe),
                        'delete' => $user->can('delete', $classe),
                    ],
                ];
            });

        return Inertia::render('tenant/classes/index', [
            'classes' => $classes,
            'can' => [
                'create' => $user->can('create', Classe::class),
            ],
        ]);
    }

    /**
     * Mostra o formulário para criar uma nova classe.
     */
    public function create()
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        return Inertia::render('tenant/classes/create', [
            'can' => [
                'create' => $user->can('create', Classe::class),
            ],
        ]);
    }

    /**
     * Guarda uma nova classe no tenant actual.
     */
    public function store(StoreClasseRequest $request)
    {
        $this->createClasse->handle($request->validated());

        return to_route('tenant.dashboard.classes.index')->with('toast', [
            'type' => 'success',
            'message' => 'Classe criada com sucesso.',
        ]);
    }

    /**
     * Mostra os dados de uma classe específica.
     */
    public function show(Classe $classe)
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        return Inertia::render('tenant/classes/show', [
            'classe' => $classe,
            'can' => [
                'view' => $user->can('view', $classe),
                'edit' => $user->can('update', $classe),
                'delete' => $user->can('delete', $classe),
            ],
        ]);
    }

    /**
     * Mostra o formulário para editar uma classe específica.
     */
    public function edit(Classe $classe)
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        return Inertia::render('tenant/classes/edit', [
            'classe' => $classe,
            'can' => [
                'edit' => $user->can('update', $classe),
            ],
        ]);
    }

    /**
     * Actualiza uma classe específica.
     */
    public function update(UpdateClasseRequest $request, Classe $classe)
    {
        $this->updateClasse->handle($classe, $request->validated());

        return to_route('tenant.dashboard.classes.index')->with('toast', [
            'type' => 'success',
            'message' => 'Classe actualizada com sucesso.',
        ]);
    }

    /**
     * Remove uma classe específica.
     */
    public function destroy(Classe $classe)
    {
        $this->deleteClasse->handle($classe);

        return to_route('tenant.dashboard.classes.index')->with('toast', [
            'type' => 'success',
            'message' => 'Classe removida com sucesso.',
        ]);
    }
}
