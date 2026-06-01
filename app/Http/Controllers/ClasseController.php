<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClasseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $classes = Classe::all();

        return Inertia::render('classes/index', [
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
    public function store(Request $request)
    {
        $classe = Classe::create($request->all());

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
    public function update(Request $request, Classe $classe) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Classe $classe)
    {
        //
    }
}
