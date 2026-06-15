<?php

namespace App\Http\Controllers;

use App\Http\Requests\Classe\StoreClasseRequest;
use App\Http\Requests\Classe\UpdateClasseRequest;
use App\Models\Classe;
use Inertia\Inertia;

class ClasseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $classes = Classe::select(['id', 'nome', 'created_at'])
            ->orderBy('nome', 'asc')
            ->paginate(10);

        return Inertia('classes/index', [
            'classes' => $classes,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('classes/create');
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
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Classe $classe)
    {
        return Inertia::render('classes/edit', [
            'classe' => $classe,
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
