<?php

namespace App\Http\Controllers;

use App\Http\Requests\Classe\StoreClasseRequest;
use App\Http\Requests\Classe\UpdateClasseRequest;
use App\Models\Classe;
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
        $classes = Classe::select(['id', 'nome', 'created_at'])
            ->orderBy('nome', 'asc')
            ->paginate(10)
            ->through(function ($classe) {
                return [
                    'id' => $classe->id,
                    'nome' => $classe->nome,
                    'can' => [
                        'view_classe' => Auth::user()->can('view', $classe),
                        'edit_classe' => Auth::user()->can('update', $classe),
                        'delete_classe' => Auth::user()->can('delete', $classe),
                    ],
                ];
            });

        return Inertia::render('classes/index', [
            'classes' => $classes,
            'can' => [
                'create_classe' => Auth::user()->can('create', Classe::class),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('classes/create', [
            'can' => [
                'create_classe' => Auth::user()->can('create', Classe::class),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClasseRequest $request)
    {
        Classe::create($request->validated());

        return to_route('classes.index')->with('toast', [
            'type' => 'success',
            'message' => 'Classe criada com sucesso.',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Classe $classe)
    {
        return Inertia::render('classes/show', [
            'classe' => $classe,
            'can' => [
                'view_classe' => Auth::user()->can('view', $classe),
                'edit_classe' => Auth::user()->can('update', $classe),
                'delete_classe' => Auth::user()->can('delete', $classe),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Classe $classe)
    {
        return Inertia::render('classes/edit', [
            'classe' => $classe,
            'can' => [
                'edit_classe' => Auth::user()->can('update', $classe),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClasseRequest $request, Classe $classe)
    {
        $classe->update($request->validated());

        return to_route('classes.index')->with('toast', [
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

        return to_route('classes.index')->with('toast', [
            'type' => 'success',
            'message' => 'Classe removida com sucesso.',
        ]);
    }
}
