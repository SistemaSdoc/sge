<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClasseTurnoDisciplinaHorarioRequest;
use App\Models\Tenant\ClasseTurnoDisciplina;
use App\Models\Tenant\ClasseTurnoDisciplinaHorario;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ClasseTurnoDisciplinaHorarioController extends Controller
{
    /**
     * Salvar horários de uma disciplina dentro de uma turma
     */
    public function store(
        StoreClasseTurnoDisciplinaHorarioRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        ClasseTurnoDisciplina $classeTurnoDisciplina
    ) {
        Gate::authorize('create', new ClasseTurnoDisciplinaHorario);

        // Remover horários antigos
        $classeTurnoDisciplina->horarios()->delete();

        // Criar novos horários
        $horarios = collect($request->validated()['horarios'])
            ->map(fn ($horario) => array_merge($horario, [
                'id' => (string) Str::uuid7(),
                'classe_turno_disciplina_id' => $classeTurnoDisciplina->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]))
            ->toArray();

        ClasseTurnoDisciplinaHorario::insert($horarios);

        // Preservar filtro de ano_lectivo
        $url = url()->previous() ?? route('dashboard');

        if ($anoLectivoId = request('ano_lectivo_id')) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator.'ano_lectivo_id='.$anoLectivoId;
        }

        return redirect($url);
    }
}
