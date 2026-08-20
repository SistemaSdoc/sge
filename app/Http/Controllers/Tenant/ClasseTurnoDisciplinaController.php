<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\ClasseTurnoDisciplina;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Disciplina;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaDisciplinaProfessor;
use App\Services\AnoLectivo\AnoLectivoResolverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ClasseTurnoDisciplinaController extends Controller
{
    public function __construct(private readonly AnoLectivoResolverService $anoLectivoResolverService) {}

    public function create(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno
    ) {
        $this->authorize('create', ClasseTurnoDisciplina::class);

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/disciplinas/create', [
            'disciplinas' => Disciplina::select('id', 'nome')->orderBy('nome')->get(),
            'instituicao' => $instituicao->only('id'),
            'cursoTutelado' => [
                'id' => $cursoTutelado->id,
                'nome' => $cursoTutelado->instituicaoCurso->curso->nome,
            ],
            'cursoClasse' => [
                'id' => $cursoClasse->id,
                'nome' => $cursoClasse->classe->nome,
            ],
            'cursoClasseTurno' => [
                'id' => $cursoClasseTurno->id,
                'nome' => $cursoClasseTurno->turno->nome,
            ],
        ]);
    }

    public function store(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno
    ) {
        $this->authorize('create', ClasseTurnoDisciplina::class);

        $request->validate([
            'disciplina_ids' => 'required|array|min:1',
            'disciplina_ids.*' => 'exists:disciplinas,id',
            'carga_horaria' => 'nullable|string|max:255',
            'tem_professor' => 'nullable|boolean',
        ]);

        // Determina automaticamente o ano lectivo
        $anoLectivoId = $this->anoLectivoResolverService->obterAnoLectivoDefault();

        // Buscar as que já existem para ignorar duplicadas no mesmo ano lectivo
        $jaExistentes = ClasseTurnoDisciplina::where('curso_classe_turno_id', $cursoClasseTurno->id)
            ->where('ano_lectivo_id', $anoLectivoId)
            ->whereIn('disciplina_id', $request->disciplina_ids)
            ->pluck('disciplina_id')
            ->toArray();

        $novas = array_diff($request->disciplina_ids, $jaExistentes);

        if (empty($novas)) {
            return back()->withErrors([
                'disciplina_ids' => 'Todas as disciplinas já estão associadas.',
            ]);
        }

        foreach (array_values($novas) as $disciplinaId) {
            ClasseTurnoDisciplina::create([
                'curso_classe_turno_id' => $cursoClasseTurno->id,
                'disciplina_id' => $disciplinaId,
                'carga_horaria' => $request->carga_horaria,
                'tem_professor' => $request->tem_professor ?? false,
                'ano_lectivo_id' => $anoLectivoId,
            ]);
        }

        return redirect()->intended(route('cursos-tutelados.classes.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'ano_lectivo_id' => $anoLectivoId,
        ]))->with('success', 'Turma criada com sucesso!');

    }

    public function edit(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        ClasseTurnoDisciplina $classeTurnoDisciplina
    ) {
        $this->authorize('update', $classeTurnoDisciplina);

        return Inertia::render(
            'tenant/cursos-tutelados/classes/turnos/disciplinas/edit',
            [
                'disciplina' => $classeTurnoDisciplina,
                'instituicaoId' => $instituicao->id,
                'cursoId' => $cursoTutelado->id,
                'classeId' => $cursoClasse->id,
                'turnoId' => $cursoClasseTurno->id,
                'anoLectivoId' => $classeTurnoDisciplina->ano_lectivo_id, // ← directo do registo
            ]
        );
    }

    public function update(
        Request $request,
        Instituicao $instituicao,
        InstituicaoCurso $instituicaoCurso,
        Turma $turma,
        ClasseTurnoDisciplina $classeTurnoDisciplina
    ) {
        $this->authorize('update', $classeTurnoDisciplina); // ← corrigir permissão (era 'delete')

        DB::transaction(function () use ($request, $classeTurnoDisciplina) {

            $classeTurnoDisciplina->update([
                'carga_horaria' => $request->carga_horaria,
                'tem_professor' => $request->filled('professor_id'),
            ]);

            // Actualizar professor se veio no request
            if ($request->filled('professor_id')) {
                TurmaDisciplinaProfessor::updateOrCreate(
                    ['classe_turno_disciplina_id' => $classeTurnoDisciplina->id],
                    ['professor_id' => $request->professor_id]
                );
            }
        });

        return response()->json(['message' => 'Actualizado com sucesso.'], 200);
    }

    public function destroy(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        ClasseTurnoDisciplina $classeTurnoDisciplina
    ) {
        $this->authorize('delete', $classeTurnoDisciplina);

        abort_if($classeTurnoDisciplina->curso_classe_turno_id !== $cursoClasseTurno->id, 404);

        // Verificar se tem professores associados
        $temProfessores = $classeTurnoDisciplina->turmaDisciplinaProfessores()->exists();

        if ($temProfessores) {
            return back()->withErrors([
                'message' => 'Não é possível remover uma disciplina com professores associados.',
            ]);
        }

        $classeTurnoDisciplina->delete();

        // Preservar filtro no redirect
        $anoLectivoParam = $classeTurnoDisciplina->ano_lectivo_id
            ? ['ano_lectivo_id' => $classeTurnoDisciplina->ano_lectivo_id]
            : [];

        return to_route('cursos-tutelados.classes.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
        ] + $anoLectivoParam)->with('success', 'Disciplina removida com sucesso.');
    }
}
