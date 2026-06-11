<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCursoInstituicaoRequest;
use App\Http\Requests\StoreInstituicaoCursoRequest;
use App\Http\Resources\CursoTutelado\CursoTuteladoResourceIndex;
use App\Models\Classe;
use App\Models\Curso;
use App\Models\InstituicaoCurso;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class InstituicaoCursoController extends Controller //implements HasMiddleware
{
    /*public static function middleware(): array
    {
        return [
            new Middleware('permission:cursos.index',  only: ['index']),
            new Middleware('permission:cursos.show',   only: ['show']),
            new Middleware('permission:cursos.create', only: ['store']),
            new Middleware('permission:cursos.edit',   only: ['update']),
            new Middleware('permission:cursos.delete', only: ['destroy']),
        ];
    }*/

    public function store(StoreInstituicaoCursoRequest $request, Instituicao $instituicao)
    {
        $validated = $request->validated();

        if ($validated['curso_id'] ?? null) {
            $curso = Curso::findOrFail($validated['curso_id']);
        } else {
            $curso = Curso::firstOrCreate(
                ['nome' => $validated['nome']],
                ['duracao_anos' => $validated['duracao_anos']]
            );
        }

        $cursoExistente = InstituicaoCurso::where('instituicao_id', $instituicao->id)
            ->where('curso_id', $curso->id)
            ->exists();

        if ($cursoExistente) {
            abort(422, 'Esta instituição já tem este curso associado.');
        }

        $instituicaoCurso = InstituicaoCurso::create([
            'curso_id' => $curso->id,
            'instituicao_id' => $instituicao->id,
        ]);

        $instituicaoCurso->cursoTutelado()->create([
            'instituicao_tutora_id' => $instituicao->id,
        ]);

        return response()->noContent(201);
    }

    public function update(Request $request, Instituicao $instituicao, InstituicaoCurso $instituicaoCurso)
    {
        abort_if($instituicaoCurso->instituicao_id !== $instituicao->id, 404);

        $request->validate([
            'nome' => 'required|string|max:255',
            'duracao_anos' => 'nullable|integer|min:1',
        ]);

        // Verificar se algum turno a remover tem turmas associadas
        $turnosActuais = $instituicaoCurso->cursoTutelado->cursoClasseTurnos->pluck('turno_id');
        $turnosNovos = collect($request->turnos);
        $turnosARemover = $turnosActuais->diff($turnosNovos);

        if ($turnosARemover->isNotEmpty()) {
            $temTurmas = $instituicaoCurso->cursoTutelado
                ->cursoClasseTurnos()
                ->whereIn('turno_id', $turnosARemover)
                ->whereHas('turmas')
                ->exists();

            if ($temTurmas) {
                return response()->json([
                    'message' => 'Não é possível remover um turno que tem turmas associadas.'
                ], 422);
            }
        }

        DB::transaction(function () use ($request, $instituicaoCurso) {
            // 1. Actualizar o curso global
            $instituicaoCurso->curso->update([
                'nome' => $request->nome,
                'duracao_anos' => $request->duracao_anos,
            ]);

            // 2. Actualizar as classes desta oferta
            $instituicaoCurso->classes()->sync($request->classes);

            // 3. Actualizar os turnos desta tutela
            $instituicaoCurso->cursoTutelado->turnos()->sync($request->turnos);
        });

        return response()->json(['message' => 'Curso actualizado com sucesso.']);
    }

    public function destroy(Instituicao $instituicao, InstituicaoCurso $instituicaoCurso)
    {
        abort_if($instituicaoCurso->instituicao_id !== $instituicao->id, 404);

        $instituicaoCurso->cursoTutelado()->delete();
        $instituicaoCurso->delete();

        return to_route('instituicoes.show', $instituicao)->with('toast', [
            'type' => 'success',
            'message' => 'Curso removido com sucesso.',
        ]);
    }
}