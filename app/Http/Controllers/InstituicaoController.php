<?php

namespace App\Http\Controllers;

use App\Http\Requests\InstituicoesRequest;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use App\Models\InstituicaoCurso;
use App\Models\User;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class InstituicaoController extends Controller /* implements HasMiddleware */
{
    /* public static function middleware(): array
    {
        return [
            new Middleware('permission:instituicoes.index',  only: ['index']),
            new Middleware('permission:instituicoes.show',   only: ['show']),
            new Middleware('permission:instituicoes.create', only: ['store']),
            new Middleware('permission:instituicoes.delete', only: ['destroy']),
        ];
    } */

    public function index()
    {
        /** @var User|null $user */
        $user = Auth::user();
        $instituicaoId = $user?->instituicao_id;

        $instituicoes = Instituicao::select(['id', 'nome', 'sigla', 'tipo'])
            ->orderBy('nome', 'asc')
            ->paginate(10);

        return Inertia::render('instituicoes/index', [
            'instituicoes' => $instituicoes,
        ]);
    }

    public function create()
    {
        return Inertia::render('instituicoes/create');
    }

    public function store(InstituicoesRequest $request)
    {
        $dados = $request->validated();

        if ($request->hasFile('logo')) {
            $dados['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $instituicao = Instituicao::create($dados);

        if ($request->has('cursos')) {
            foreach ($request->cursos as $cursoId) {

                $instituicaoCurso = InstituicaoCurso::create([
                    'instituicao_id' => $instituicao->id,
                    'curso_id' => $cursoId,
                ]);

                //  tutela padrão = própria instituição
                CursoTutelado::create([
                    'instituicao_curso_id' => $instituicaoCurso->id,
                    'instituicao_tutora_id' => $instituicao->id,
                ]);
            }
        }

        return to_route('instituicoes.index')->with('toast', [
            'type' => 'success',
            'message' => 'Instituição atualizada com sucesso!',
        ]);
    }

    public function show(Instituicao $instituicao)
    {
        /** @var User|null $user */
        $user = Auth::user();
        $instituicaoId = $user ? $user->instituicaoFiltro() : null;

        // Se não for Super Admin, só pode ver a sua instituição
        if ($instituicaoId && $instituicao->id !== $instituicaoId) {
            abort(403, 'Sem permissão para ver esta instituição.');
        }

        $instituicao->load([
            'instituicaoCursos.curso:id,nome',
            'instituicaoCursos.cursoTutelado.instituicaoTutora:id,nome',
        ]);

        // Paginate primeiro, depois transforme os dados
        $cursosPaginated = $instituicao->instituicaoCursos()
            ->with(['curso:id,nome', 'cursoTutelado.instituicaoTutora:id,nome'])
            ->paginate(5);

        // Agora transforme os itens paginados
        $cursos = $cursosPaginated->through(fn ($instituicaoCurso) => [
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
        return Inertia::render('instituicoes/edit', [
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

        $cursosNovos = $request->input('cursos', []);
        /*
                // cursos atuais
                $cursosAtuais = $instituicao->instituicaoCurso()
                    ->pluck('curso_id')
                    ->toArray();


                $cursosRemovidos = array_diff($cursosAtuais, $cursosNovos);

                if (!empty($cursosRemovidos)) {
                    $instituicaoCursosRemover = $instituicao->instituicaoCurso()
                        ->whereIn('curso_id', $cursosRemovidos)
                        ->get();

                    foreach ($instituicaoCursosRemover as $ic) {
                        $ic->cursoTutelado()->delete();
                        $ic->delete();
                    }
                }


                $cursosAdicionados = array_diff($cursosNovos, $cursosAtuais);

                foreach ($cursosAdicionados as $cursoId) {

                    $instituicaoCurso = InstituicaoCurso::create([
                        'instituicao_id' => $instituicao->id,
                        'curso_id' => $cursoId,
                    ]);

                    CursoTutelado::create([
                        'instituicao_curso_id' => $instituicaoCurso->id,
                        'instituicao_tutora_id' => $instituicao->id,
                    ]);
                }*/

        return to_route('instituicoes.index')->with('toast', [
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

        return to_route('instituicoes.index')->with('toast', [
            'type' => 'success',
            'message' => 'Instituição excluída com sucesso!',
        ]);
    }
}
