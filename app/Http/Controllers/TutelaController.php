<?php

namespace App\Http\Controllers;

use App\Http\Requests\TutelaRequest;
use App\Models\Curso;
use App\Models\CursoTutelado;
use App\Models\InstituicaoCurso;
use Illuminate\Http\Request;

class TutelaController extends Controller
{
   

    public function update(Request $request, CursoTutelado $cursoTutelado)
    {
        $cursoTutelado->update([
            'instituicao_tutora_id' => $request->instituicao_tutora_id,
        ]);

        return response()->noContent();
    }
}
