<?php

namespace App\Http\Controllers\Tenant\Colegios;

use App\Http\Controllers\Controller;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ColegioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Instituicao $instituicao)
    {
        // $this->authorize('tutelar', $instituicao);

        // 1. Buscar IDs dos cursos tutelados desta instituição tutora
        $cursoTuteladoIds = CursoTutelado::where('instituicao_tutora_id', $instituicao->id)
            ->pluck('instituicao_curso_id');

        // 2. Buscar IDs das instituições (colégios) que têm esses cursos
        $instituicaoIds = InstituicaoCurso::whereIn('id', $cursoTuteladoIds)
            ->distinct()
            ->pluck('instituicao_id');

        // 3. Paginar os colégios
        $colegios = Instituicao::whereIn('id', $instituicaoIds)
            ->where('tipo', 'colegio')
            ->select('id', 'nome', 'tipo')
            ->orderBy('nome')
            ->paginate(5);

        // 4. Carregar os cursos tutelados de cada colégio
        $colegiosComCursos = $colegios->getCollection()->map(function ($colegio) use ($instituicao) {
            $cursos = InstituicaoCurso::where('instituicao_id', $colegio->id)
                ->whereHas('cursoTutelado', fn ($q) => $q->where('instituicao_tutora_id', $instituicao->id))
                ->with(['curso:id,nome', 'cursoTutelado:id,instituicao_curso_id'])
                ->get();

            return [
                'id' => $colegio->id,
                'nome' => $colegio->nome,
                'tipo' => $colegio->tipo,
                'total_cursos' => $cursos->count(),
                'cursos' => $cursos->map(fn ($ic) => [
                    'id' => $ic->cursoTutelado->id,
                    'nome' => $ic->curso->nome,
                    'curso_tutelado_id' => $ic->cursoTutelado->id,
                ])->toArray(),
            ];
        });

        $colegios->setCollection($colegiosComCursos);

        return Inertia::render('tenant/colegio/index', [
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
            ],
            'colegios' => $colegios,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $instituicao, string $colegio)
    {
        $instituicao = Instituicao::findOrFail($instituicao);
        $colegio = Instituicao::findOrFail($colegio);

        $cursos = InstituicaoCurso::where('instituicao_id', $colegio->id)
            ->whereHas('cursoTutelado', function ($query) use ($instituicao) {
                $query->where('instituicao_tutora_id', $instituicao->id);
            })
            ->with([
                'curso:id,nome',
                'cursoTutelado:id,instituicao_curso_id',
            ])
            ->paginate(10);

        $cursosFormatados = $cursos->getCollection()->map(
            fn ($ic) => [
                'id' => $ic->cursoTutelado->id,
                'nome' => $ic->curso->nome,
                'curso_tutelado_id' => $ic->cursoTutelado->id,
            ]
        );

        $cursos->setCollection($cursosFormatados);

        return Inertia::render('tenant/colegio/show', [
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
            ],
            'colegio' => [
                'id' => $colegio->id,
                'nome' => $colegio->nome,
            ],
            'can' => [
                'gerir_prazos' => auth()->user()->can('pautas.gerirPrazos'),
            ],
            'cursos' => $cursos,
        ]);
    }
}
