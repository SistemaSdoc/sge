<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Classe\StoreClasseRequest;
use App\Http\Requests\Classe\UpdateClasseRequest;
use App\Models\Tenant\Classe;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ClasseController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Classe::class, 'classe', [
            'except' => [],
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $classes = Classe::select(['id', 'nome', 'nivel_ensino', 'created_at'])
            ->orderBy('nome', 'asc')
            ->paginate(10)
            ->through(function ($classe) use ($user) {
                return [
                    'id' => $classe->id,
                    'nome' => $classe->nome,
                    'nivel_ensino' => $classe->nivel_ensino,
                    'can' => [
                        'view_classe' => $user->can('view', $classe),
                        'edit_classe' => $user->can('update', $classe),
                        'delete_classe' => $user->can('delete', $classe),
                    ],
                ];
            });

        return Inertia::render('tenant/classes/index', [
            'classes' => $classes,
            'can' => [
                'create_classe' => $user->can('create', Classe::class),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        return Inertia::render('tenant/classes/create', [
            'can' => [
                'create_classe' => $user->can('create', Classe::class),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClasseRequest $request)
    {
        Classe::create($request->validated());

        return to_route('tenant.dashboard.classes.index')->with('toast', [
            'type' => 'success',
            'message' => 'Classe criada com sucesso.',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Classe $classe)
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        return Inertia::render('tenant/classes/show', [
            'classe' => $classe,
            'can' => [
                'view_classe' => $user->can('view', $classe),
                'edit_classe' => $user->can('update', $classe),
                'delete_classe' => $user->can('delete', $classe),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Classe $classe)
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        return Inertia::render('tenant/classes/edit', [
            'classe' => $classe,
            'can' => [
                'edit_classe' => $user->can('update', $classe),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClasseRequest $request, Classe $classe)
    {
        $classe->update($request->validated());

        return to_route('tenant.dashboard.classes.index')->with('toast', [
            'type' => 'success',
            'message' => 'Classe actualizada com sucesso.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Classe $classe)
    {
        $classe->delete();

        return to_route('tenant.dashboard.classes.index')->with('toast', [
            'type' => 'success',
            'message' => 'Classe removida com sucesso.',
        ]);
    }
}
