<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstituicoesRequest;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class InstituicaoController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Instituicao::class, 'instituicao', [
            'except' => [],
        ]);
    }

    public function index()
    {
        $instituicoes = Instituicao::select(['id', 'nome', 'sigla', 'tipo'])
            ->orderBy('nome', 'asc')
            ->paginate(10)
            ->through(function ($instituicao) {
                return [
                    'id' => $instituicao->id,
                    'nome' => $instituicao->nome,
                    'sigla' => $instituicao->sigla,
                    'tipo' => $instituicao->tipo,
                    'can' => [
                        'view_instituicao' => Auth::guard('tenant')->user()->can('view', $instituicao),
                        'edit_instituicao' => Auth::guard('tenant')->user()->can('update', $instituicao),
                        'delete_instituicao' => Auth::guard('tenant')->user()->can('delete', $instituicao),
                    ],
                ];
            });

        return Inertia::render('tenant/instituicoes/index', [
            'can' => [
                'create_instituicao' => Auth::guard('tenant')->user()->can('create', Instituicao::class),
            ],
            'instituicoes' => $instituicoes,
        ]);
    }

    public function create()
    {
        return Inertia::render('tenant/instituicoes/create', [
            'can' => [
                'create_instituicao' => Auth::guard('tenant')->user()->can('create', Instituicao::class),
            ],
        ]);
    }

    public function store(InstituicoesRequest $request)
    {
        $dados = $request->validated();

        if ($request->hasFile('logo')) {
            $dados['logo'] = $request->file('logo')->store('logos', 'public');
        }

        Instituicao::create($dados);

        return to_route('tenant.dashboard.instituicoes.index')->with('toast', [
            'type' => 'success',
            'message' => 'Instituição criada com sucesso!',
        ]);
    }

    public function show(Instituicao $instituicao)
    {
        $cursos = $instituicao->instituicaoCursos()
            ->with(['curso:id,nome', 'cursoTutelado.instituicaoTutora:id,nome'])
            ->paginate(5)
            ->through(fn ($instituicaoCurso) => [
                'id' => $instituicaoCurso->cursoTutelado->id,
                'nome' => $instituicaoCurso->curso->nome,
                'instituicao_tutora' => $instituicaoCurso->cursoTutelado?->instituicaoTutora?->nome,
                'can' => [
                    'view' => Auth::guard('tenant')->user()->can('view', $instituicaoCurso->cursoTutelado),
                    'update' => Auth::guard('tenant')->user()->can('update', $instituicaoCurso->cursoTutelado),
                    'delete' => Auth::guard('tenant')->user()->can('delete', $instituicaoCurso->cursoTutelado),
                ],
            ]);

        return Inertia::render('tenant/instituicoes/show', [
            'can' => [
                'edit_instituicao' => Auth::guard('tenant')->user()->can('update', $instituicao),
                'create_curso' => Auth::guard('tenant')->user()->can('create', CursoTutelado::class),
                'view_instituicao' => Auth::guard('tenant')->user()->can('view', $instituicao),
                'gerir_prazos' => Auth::guard('tenant')->user()->can('pautas.gerirPrazos'),
            ],
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
                'sigla' => $instituicao->sigla,
                'tipo' => $instituicao->tipo,
                'email' => $instituicao->email,
                'telefone' => $instituicao->telefone,
                'endereco' => $instituicao->endereco,
                'logo' => $instituicao->logo,
                'descricao' => $instituicao->descricao,
            ],
            'cursos' => $cursos,
            'storageUrl' => asset('storage'),
        ]);
    }

    public function edit(Instituicao $instituicao)
    {
        return Inertia::render('tenant/instituicoes/edit', [
            'can' => [
                'update_instituicao' => Auth::guard('tenant')->user()->can('update', $instituicao),
            ],
            'instituicao' => $instituicao,
        ]);
    }

    public function update(InstituicoesRequest $request, Instituicao $instituicao)
    {
        $dados = $request->validated();

        if ($request->hasFile('logo')) {
            if ($instituicao->logo) {
                Storage::disk('public')->delete($instituicao->logo);
            }

            $dados['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $instituicao->update($dados);

        return to_route('tenant.dashboard.instituicoes.show', $instituicao)->with('toast', [
            'type' => 'success',
            'message' => 'Instituição atualizada com sucesso!',
        ]);
    }

    public function destroy(Instituicao $instituicao)
    {
        if ($instituicao->logo) {
            Storage::disk('public')->delete($instituicao->logo);
        }

        $instituicao->delete();

        return to_route('tenant.dashboard.instituicoes.index')->with('toast', [
            'type' => 'success',
            'message' => 'Instituição excluída com sucesso!',
        ]);
    }
}
