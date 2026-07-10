<?php

namespace App\Http\Controllers;

use App\Http\Requests\Curso\CursoStoreRequest;
use App\Http\Requests\Curso\CursoUpdateRequest;
use App\Models\Curso;
use App\Models\InstituicaoCurso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CursosController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Curso::class, 'curso', [
            'except' => [],
        ]);
    }

    public function index()
    {
        $cursos = Curso::select(['id', 'nome', 'created_at'])
            ->orderBy('nome', 'asc')
            ->paginate(10)
            ->through(function ($curso) {
                return [
                    'id' => $curso->id,
                    'nome' => $curso->nome,
                    'can' => [
                        'view_curso' => Auth::user()->can('view', $curso),
                        'edit_curso' => Auth::user()->can('update', $curso),
                        'delete_curso' => Auth::user()->can('delete', $curso),
                    ],
                ];
            });

        return Inertia::render('cursos/index', [
            'cursos' => $cursos,
            'can' => [
                'create_curso' => Auth::user()->can('create', Curso::class),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('cursos/create', [
            'can' => [
                'create_curso' => Auth::user()->can('create', Curso::class),
            ],
        ]);
    }

    public function store(CursoStoreRequest $request)
    {
        $request->validated();

        $curso = Curso::create([
            'nome' => $request->nome,
            'duracao_anos' => $request->duracao_anos,
            'descricao' => $request->descricao,
            'status' => 1,
        ]);

        return to_route('cursos.index')->with('toast', [
            'type' => 'success',
            'message' => 'Curso criado com sucesso!',
        ]);
    }

    public function show(Curso $curso)
    {
        return Inertia::render('cursos/show', [
            'curso' => $curso,
            'can' => [
                'update_curso' => Auth::user()->can('update', $curso),
                'delete_curso' => Auth::user()->can('delete', $curso),
                'view_curso' => Auth::user()->can('view', $curso),
            ],
        ]);
    }

    public function edit(Curso $curso)
    {
        return Inertia::render('cursos/edit', [
            'curso' => $curso,
            'can' => [
                'update_curso' => Auth::user()->can('update', $curso),
            ],
        ]);
    }

    public function update(CursoUpdateRequest $request, Curso $curso)
    {
        $request->validated();

        $curso->update([
            'nome' => $request->nome,
            'duracao_anos' => $request->duracao_anos,
            'descricao' => $request->descricao,
        ]);

        $curso->update($request->all());

        return to_route('cursos.index')->with('toast', [
            'type' => 'success',
            'message' => 'Curso atualizado com sucesso!',
        ]);
    }

    public function destroy(Curso $curso)
    {
        $curso->delete();

        return to_route('cursos.index')->with('toast', [
            'type' => 'success',
            'message' => 'Curso excluído com sucesso!',
        ]);
    }

    public function instituicoesTutoras(Curso $curso, Request $request)
    {
        $instituicaoId = $request->query('instituicao_id');

        $instituicoes = InstituicaoCurso::with('instituicao')
            ->where('curso_id', $curso->id)
            ->whereHas(
                'instituicao',
                fn ($q) => $q->where('tipo', 'instituto')
                    ->orWhere('id', $instituicaoId)
            )
            ->get()
            ->pluck('instituicao')
            ->unique('id')
            ->values()
            ->map(fn ($inst) => [
                'id' => $inst->id,
                'nome' => $inst->nome,
            ]);

        return response()->json(['data' => $instituicoes]);
    }
}
