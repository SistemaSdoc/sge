<?php

namespace App\Http\Controllers;

use App\Models\CursoInstituicao;
use App\Models\Instituicao;
use App\Models\InstituicaoCurso;
use App\Models\Turno;
use Illuminate\Http\Request;

class CursoTurnoController extends Controller
{
    public function index()
    {
        //
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(string $id)
    {
        //
    }

    public function edit(Instituicao $instituicao, InstituicaoCurso $instituicaoCurso)
    {
        $cursoTutelado = $instituicaoCurso->cursoTutelado; // já existe no teu sistema
        $turnos = Turno::all();

        $turnosSelecionados = $cursoTutelado
            ? $cursoTutelado->turnos->pluck('id')->toArray()
            : [];

        return view('cursos.turnos', compact(
            'instituicao',
            'instituicaoCurso',
            'cursoTutelado',
            'turnos',
            'turnosSelecionados'
        ));
    }

    public function update(Request $request, Instituicao $instituicao, InstituicaoCurso $cursoInstituicao)
    {
        $cursoTutelado = $cursoInstituicao->cursoTutelado;

        $turnos = $request->input('turnos', []);

        // sincroniza na pivot turno_instituicao_curso
        $cursoTutelado->turnos()->sync($turnos);

        return redirect()
            ->route('instituicoes.show', $instituicao)
            ->with('success', 'Turnos atualizados com sucesso!');
    }

}
    