<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\CursoTuteladoProfessor;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Professor;
use App\Models\Tenant\User;
use App\Traits\NotificaProfessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CursoTuteladoProfessorController extends Controller
{
    use NotificaProfessor;

    /**
     * Display a listing of the resource.
     */
    public function index(Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $instituicaoId = $user?->instituicaoFiltro();

        $professores = $cursoTutelado->professores()
            ->with(['user'])
            ->paginate(5);

        return response()->json(
            $professores->through(fn($prof) => [
                'id' => $prof->id,
                'nome' => $prof->user?->nome,
                'email' => $prof->user?->email,
                'tipo' => $prof->pivot->tipo,
                'coordenador' => $prof->pivot->coordenador,
                'opap' => $prof->pivot->opap,
                'grupo_disciplinar' => $prof->pivot->grupo_disciplinar,
            ])
        );
    }

    public function create(Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        $professores = Professor::with('user:id,nome')
            ->whereHas('user', fn ($q) => $q->where('instituicao_id', $instituicao->id))
            ->whereDoesntHave(
                'cursosTutelados',
                fn ($q) => $q->whereKey($cursoTutelado->getKey())
            )
            ->orderBy('id')
            ->get();

        return Inertia::render('tenant/cursos-tutelados/professores/create', [
            'professores' => $professores,
            'instituicaoId' => $instituicao->id,
            'cursoTuteladoId' => $cursoTutelado->id,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    //  Atribuir ou atualizar professor no curso
    public function store(Request $request, Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        $this->authorize('manageProfessores', $cursoTutelado);

        $request->validate([
            'professor_id' => 'required|exists:professores,id',
            'tipo' => 'required|in:principal,colaborador',
            'coordenador' => 'boolean',
            'opap' => 'boolean',
            'grupo_disciplinar' => 'nullable|in:nenhum,membro,coordenador',
        ]);

        if ($request->boolean('coordenador')) {
            $jaTemCoordenador = CursoTuteladoProfessor::where('curso_tutelado_id', $cursoTutelado->id)
                ->where('professor_id', '!=', $request->professor_id)
                ->where('coordenador', true)
                ->exists();

            if ($jaTemCoordenador) {
                return back()->withErrors([
                    'coordenador' => 'Este curso tutelado já tem um coordenador definido.',
                ]);
            }
        }

        if ($request->grupo_disciplinar === 'coordenador') {
            $jaTemCoordenadorGrupo = CursoTuteladoProfessor::where('curso_tutelado_id', $cursoTutelado->id)
                ->where('professor_id', '!=', $request->professor_id)
                ->where('grupo_disciplinar', 'coordenador')
                ->exists();

            if ($jaTemCoordenadorGrupo) {
                return back()->withErrors([
                    'grupo_disciplinar' => 'Este curso já tem um coordenador do grupo disciplinar.',
                ]);
            }
        }

        CursoTuteladoProfessor::updateOrCreate(
            [
                'curso_tutelado_id' => $cursoTutelado->id,
                'professor_id' => $request->professor_id,
            ],
            [
                'tipo' => $request->tipo,
                'coordenador' => $request->boolean('coordenador'),
                'opap' => $request->boolean('opap'),
                'grupo_disciplinar' => $request->grupo_disciplinar ?? 'nenhum',
            ]
        );

        $professor = Professor::find($request->professor_id);

        $professor->user->removeRole('Membro do Grupo Disciplinar');
        $professor->user->removeRole('Coordenador do Grupo Disciplinar');

        match ($request->grupo_disciplinar) {
            'membro' => $professor->user->assignRole('Membro do Grupo Disciplinar'),
            'coordenador' => $professor->user->assignRole('Coordenador do Grupo Disciplinar'),
            default => null,
        };

        $this->notificarProfessorAdicionadoAoCurso($professor, $cursoTutelado);

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
        ]);
    }


    public function show(string $id)
    {
        //
    }

    public function edit(Instituicao $instituicao, CursoTutelado $cursoTutelado, $professore)
    {
        $this->authorize('manageProfessores', $cursoTutelado);

        $vinculo = CursoTuteladoProfessor::query()
            ->with('professor.user:id,nome')
            ->where('curso_tutelado_id', $cursoTutelado->id)
            ->findOrFail($professore);

        return Inertia::render('tenant/cursos-tutelados/professores/edit', [
            'vinculo' => [
                'id' => $vinculo->id,
                'professor_id' => $vinculo->professor_id,
                'nome' => $vinculo->professor?->user?->nome,
                'tipo' => $vinculo->tipo,
                'coordenador' => (bool) $vinculo->coordenador,
                'opap' => (bool) $vinculo->opap,
            ],
            'instituicaoId' => $instituicao->id,
            'cursoTuteladoId' => $cursoTutelado->id,
        ]);
    }

    public function update(Request $request, Instituicao $instituicao, CursoTutelado $cursoTutelado, $professore)
    {
        $this->authorize('manageProfessores', $cursoTutelado);

        $request->validate([
            'tipo' => 'required|in:principal,colaborador',
            'coordenador' => 'boolean',
            'opap' => 'boolean',
            'grupo_disciplinar' => 'nullable|in:nenhum,membro,coordenador',
        ]);

        if ($request->grupo_disciplinar === 'coordenador') {
            $jaTemCoordenadorGrupo = CursoTuteladoProfessor::where('curso_tutelado_id', $cursoTutelado->id)
                ->where('id', '!=', $professore)
                ->where('grupo_disciplinar', 'coordenador')
                ->exists();

            if ($jaTemCoordenadorGrupo) {
                return back()->withErrors([
                    'grupo_disciplinar' => 'Este curso já tem um coordenador do grupo disciplinar.',
                ]);
            }
        }

        $vinculo = CursoTuteladoProfessor::findOrFail($professore);

        $vinculo->update([
            'tipo' => $request->tipo,
            'coordenador' => $request->boolean('coordenador'),
            'opap' => $request->boolean('opap'),
            'grupo_disciplinar' => $request->grupo_disciplinar ?? 'nenhum',
        ]);

        $professor = $vinculo->professor;

        $professor->user->removeRole('Membro do Grupo Disciplinar');
        $professor->user->removeRole('Coordenador do Grupo Disciplinar');

        match ($request->grupo_disciplinar) {
            'membro' => $professor->user->assignRole('Membro do Grupo Disciplinar'),
            'coordenador' => $professor->user->assignRole('Coordenador do Grupo Disciplinar'),
            default => null,
        };

        return back();
    }

    public function destroy(Instituicao $instituicao, CursoTutelado $cursoTutelado, $professore)
    {
        $this->authorize('manageProfessores', $cursoTutelado);

        CursoTuteladoProfessor::query()
            ->where('curso_tutelado_id', $cursoTutelado->id)
            ->findOrFail($professore)
            ->delete();

        return back();
    }
}
