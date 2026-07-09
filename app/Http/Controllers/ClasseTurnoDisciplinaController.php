<?php

namespace App\Http\Controllers;

use App\Models\ClasseTurnoDisciplina;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\Disciplina;
use App\Models\Instituicao;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClasseTurnoDisciplinaController extends Controller
{
    public function create(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno
    ) {
        return Inertia::render('cursos-tutelados/classes/turnos/disciplinas/create', [
            'disciplinas' => Disciplina::select('id', 'nome')->orderBy('nome')->get(),
            'instituicaoId' => $instituicao->id,
            'cursoId' => $cursoTutelado->id,
            'classeId' => $cursoClasse->id,
            'turnoId' => $cursoClasseTurno->id,
            'backUrl' => url()->previous(),

        ]);
    }

    public function store(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno
    ) {
        $request->validate([
            'disciplina_ids' => 'required|array|min:1',
            'disciplina_ids.*' => 'exists:disciplinas,id',
            'carga_horaria' => 'nullable|string|max:255',
            'tem_professor' => 'nullable|boolean',
        ]);

        // Buscar as que já existem para ignorar duplicadas
        $jaExistentes = ClasseTurnoDisciplina::where('curso_classe_turno_id', $cursoClasseTurno->id)
            ->whereIn('disciplina_id', $request->disciplina_ids)
            ->pluck('disciplina_id')
            ->toArray();

        $novas = array_diff($request->disciplina_ids, $jaExistentes);

        if (empty($novas)) {
            return back()->withErrors([
                'disciplina_ids' => 'Todas as disciplinas já estão associadas.',
            ]);
        }

        $disciplinasAdicionadas = [];
        foreach (array_values($novas) as $disciplinaId) {
            $ctd = ClasseTurnoDisciplina::create([
                'curso_classe_turno_id' => $cursoClasseTurno->id,
                'disciplina_id' => $disciplinaId,
                'carga_horaria' => $request->carga_horaria,
                'tem_professor' => $request->tem_professor ?? false,
            ]);
            $disciplinasAdicionadas[] = $ctd;
        }

        $redirectTo = $request->input('redirect_to');

        return ($redirectTo)
            ? redirect($redirectTo)
            : to_route('cursos-tutelados.classes.show', [
                'instituicao' => $instituicao->id,
                'cursoTutelado' => $cursoTutelado->id,
                'cursoClasse' => $cursoClasse->id,
                'cursoClasseTurno' => $cursoClasseTurno->id,
            ]);
    }

    public function destroy(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        ClasseTurnoDisciplina $classeTurnoDisciplina
    ) {
        abort_if($classeTurnoDisciplina->curso_classe_turno_id !== $cursoClasseTurno->id, 404);

        // Verificar se tem professores associados
        $temProfessores = $classeTurnoDisciplina->turmaDisciplinaProfessores()->exists();

        if ($temProfessores) {
            return back()->withErrors([
                'message' => 'Não é possível remover uma disciplina com professores associados.',
            ]);
        }

        $classeTurnoDisciplina->delete();

        return back()->with(
            'success',
            'Disciplina removida com sucesso.'
        );
    }
}
