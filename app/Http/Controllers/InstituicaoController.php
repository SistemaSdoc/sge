<?php

namespace App\Http\Controllers;

use App\Http\Requests\InstituicoesRequest;
use App\Models\Instituicao;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class InstituicaoController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Instituicao::class);

        $instituicoes = Instituicao::select(['id', 'nome', 'sigla', 'tipo'])
            ->orderBy('nome', 'asc')
            ->paginate(10);

        return Inertia::render('instituicoes/index', [
            'instituicoes' => $instituicoes,
        ]);
    }

    public function create()
    {
        Gate::authorize('create', Instituicao::class);

        return Inertia::render('instituicoes/create');
    }

    public function store(InstituicoesRequest $request)
    {
        Gate::authorize('create', Instituicao::class);

        $dados = $request->validated();

        if ($request->hasFile('logo')) {
            $dados['logo'] = $request->file('logo')->store('logos', 'public');
        }

        Instituicao::create($dados);

        return to_route('instituicoes.index')->with('toast', [
            'type' => 'success',
            'message' => 'Instituição atualizada com sucesso!',
        ]);
    }

    public function show(Instituicao $instituicao)
    {
        Gate::authorize('view', $instituicao);

        $cursos = $instituicao->instituicaoCursos()
            ->with(['curso:id,nome', 'cursoTutelado.instituicaoTutora:id,nome'])
            ->paginate(5)
            ->through(fn ($instituicaoCurso) => [
                'id' => $instituicaoCurso->cursoTutelado->id,
                'nome' => $instituicaoCurso->curso->nome,
                'instituicao_tutora' => $instituicaoCurso->cursoTutelado?->instituicaoTutora?->nome,
            ]);

        return Inertia::render('instituicoes/show', [
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
        Gate::authorize('update', $instituicao);

        return Inertia::render('instituicoes/edit', [
            'instituicao' => $instituicao,
        ]);
    }

    public function update(InstituicoesRequest $request, Instituicao $instituicao)
    {
        Gate::authorize('update', $instituicao);

        $dados = $request->validated();

        if ($request->hasFile('logo')) {
            if ($instituicao->logo) {
                Storage::disk('public')->delete($instituicao->logo);
            }

            $dados['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $instituicao->update($dados);

        return to_route('instituicoes.index')->with('toast', [
            'type' => 'success',
            'message' => 'Instituição atualizada com sucesso!',
        ]);
    }

    public function destroy(Instituicao $instituicao)
    {
        Gate::authorize('delete', $instituicao);

        if ($instituicao->logo) {
            Storage::disk('public')->delete($instituicao->logo);
        }

        $instituicao->delete();

        return to_route('instituicoes.index')->with('toast', [
            'type' => 'success',
            'message' => 'Instituição excluída com sucesso!',
        ]);
    }
}
