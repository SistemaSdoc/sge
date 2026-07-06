<?php

namespace App\Http\Controllers;

use App\Models\CursoTutelado;
use App\Models\Turma;
use App\Services\Pauta\PautaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PautaController extends Controller
{
    public function __construct(
        private readonly PautaService $pautaService
    ) {
    }

    /**
     * Mostra a lista de cursos tutelados da instituição do user logado
     */
    public function indexCursos()
    {
        $instituicaoId = Auth::user()->instituicaoFiltro();

        $query = CursoTutelado::with([
            'instituicaoCurso:id,instituicao_id,curso_id',
            'instituicaoCurso.curso:id,nome',
            'instituicaoTutora:id,nome',
        ])->orderBy('id');

        if ($instituicaoId) {
            $query->where(function ($q) use ($instituicaoId) {
                $q->where('instituicao_tutora_id', $instituicaoId)
                    ->orWhereHas(
                        'instituicaoCurso',
                        fn($q2) =>
                        $q2->where('instituicao_id', $instituicaoId)
                    );
            });
        }

        return Inertia::render('pautas/cursos/index', [
            'cursosTutelados' => $query->get()->map(fn($ct) => [
                'id' => $ct->id,
                'curso' => $ct->instituicaoCurso?->curso,
                'instituicao' => $ct->instituicaoTutora,
                'podeEditar' => $ct->instituicaoCurso?->instituicao_id === $instituicaoId,
            ]),
        ]);
    }

    /**
     * Mostra a lista de turmas de um curso tutelado
     */
    public function indexTurmas(CursoTutelado $cursoTutelado)
    {
        $cursoTutelado->load('instituicaoCurso.curso:id,nome');

        $turmas = Turma::whereHas(
            'cursoClasseTurno.cursoClasse',
            fn($q) => $q->where('curso_tutelado_id', $cursoTutelado->id)
        )
            ->with([
                'cursoClasseTurno.cursoClasse.classe:id,nome',
                'cursoClasseTurno.turno:id,nome',
            ])
            ->orderBy('nome')
            ->get();

        return Inertia::render('pautas/turmas/index', [
            'cursoTutelado' => [
                'id' => $cursoTutelado->id,
                'curso' => [
                    'id' => $cursoTutelado->instituicaoCurso?->curso?->id,
                    'nome' => $cursoTutelado->instituicaoCurso?->curso?->nome,
                ],
            ],
            'turmas' => $turmas->map(fn($turma) => [
                'id' => $turma->id,
                'nome' => $turma->nome,
                'classe' => $turma->cursoClasseTurno?->cursoClasse?->classe?->nome,
                'turno' => $turma->cursoClasseTurno?->turno?->nome,
            ]),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function pauta(CursoTutelado $cursoTutelado, Turma $turma, Request $request)
    {
        $filtro = $request->query('filtro');

        abort_if(
            !$turma->cursoClasseTurno?->cursoClasse?->where('curso_tutelado_id', $cursoTutelado->id)->exists(),
            404
        );

        $periodo = $request->query('periodo', '1');
        $perPage = min((int) $request->query('per_page', 10), 100);

        $turma->load([
            'cursoClasseTurno.cursoClasse.classe:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'cursoClasseTurno.turno:id,nome',
        ]);

        return Inertia::render('pautas/index', [
            'cursoTutelado' => $cursoTutelado->only('id'),
            'pauta' => $this->pautaService->gerarPauta($turma, $periodo, $perPage, $filtro),
            'periodo' => $periodo,
            'filtro' => $filtro,
        ]);
    }
}
