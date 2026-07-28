<?php

namespace App\Http\Controllers;

use App\Models\CursoTutelado;
use App\Models\CursoTuteladoProfessor;
use App\Models\Instituicao;
use App\Models\Professor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CursoTuteladoProfessorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        $instituicaoId = Auth::user()?->instituicaoFiltro();
        $professores = $cursoTutelado->professores()
            ->with(['user'])
            ->paginate(5);

        return response()->json(
            $professores->through(fn ($prof) => [
                'id' => $prof->id,
                'nome' => $prof->user?->nome,
                'email' => $prof->user?->email,
                'tipo' => $prof->pivot->tipo,
                'coordenador' => $prof->pivot->coordenador,
            ])
        );
    }

    public function create(Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        $professores = Professor::with('user:id,nome')
            ->whereHas('user', fn ($q) => $q->where('instituicao_id', $instituicao->id))
            ->orderBy('id')
            ->get();

        return Inertia::render('cursos-tutelados/professores/create', [
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
        ]);

        CursoTuteladoProfessor::updateOrCreate(
            [
                'curso_tutelado_id' => $cursoTutelado->id,
                'professor_id' => $request->professor_id,
            ],
            [
                'tipo' => $request->tipo,
                'coordenador' => $request->boolean('coordenador'),
            ]
        );

        return to_route('cursos-tutelados.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
        ]);
    }

    public function show(string $id)
    {
        //
    }

    public function edit($id) {}

    public function update(Request $request, Instituicao $instituicao, CursoTutelado $cursoTutelado, $professore)
    {
        $request->validate([
            'tipo' => 'required|in:principal,colaborador',
        ]);

        $vinculo = CursoTuteladoProfessor::findOrFail($professore);

        $vinculo->update(['tipo' => $request->tipo]);

        return back();
    }

    public function destroy(Instituicao $instituicao, CursoTutelado $cursoTutelado, $professore)
    {
        CursoTuteladoProfessor::findOrFail($professore)->delete();

        return back();
    }
}
