<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CursoClasse;
use App\Http\Resources\CursoClasse\CursoClasseResource;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CursoClasseController extends Controller implements HasMiddleware
{

    public static function middleware(): array
    {
        return [
            new Middleware('permission:cursos.show', only: ['show']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index() {}

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Display the specified resource.
     */
    public function show(Instituicao $instituicao, CursoTutelado $cursoTutelado, CursoClasse $cursoClasse)
    {
        $cursoClasse->load([
            'classe:id,nome',
            'cursoTutelado.instituicaoCurso.curso:id,nome',
            'turnos.turno:id,nome',
            'turnos.classeTurnoDisciplinas.disciplina:id,nome,sigla,componente',
        ]);

        return new CursoClasseResource($cursoClasse);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id) {}
}
