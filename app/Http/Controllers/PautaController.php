<?php

namespace App\Http\Controllers;

use App\Models\AnoLectivo;
use App\Models\CursoTutelado;
use App\Models\Turma;
use App\Services\AnoLectivo\AnoLectivoResolverService;
use App\Services\Pauta\PautaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PautaController extends Controller
{
    public function __construct(
        private readonly PautaService $pautaService,
        private readonly AnoLectivoResolverService $anoLectivoResolverService
    ) {}

    /**
     * Mostra a lista de cursos tutelados da instituição do user logado
     */
    public function indexCursos()
    {
        $this->authorize('pauta.viewAny', CursoTutelado::class);

        $user = Auth::user();
        $instituicaoId = $user?->instituicao_id;
        $isProfessor = $user->hasRole('Professor');
        $professorId = $user->professor?->id;

        // Filtro ano lectivo
        $anoLectivoId = request('ano_lectivo_id')
            ?? AnoLectivo::activo()?->id;

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
                        fn ($q2) => $q2->where('instituicao_id', $instituicaoId)
                    );
            });
        }

        if ($isProfessor) {
            $query->whereHas(
                'professores',
                fn ($q) => $q->where('professor_id', $professorId)
            );
        }

        return Inertia::render('pautas/cursos/index', [
            'cursosTutelados' => $query->get()->map(fn ($ct) => [
                'id' => $ct->id,
                'curso' => $ct->instituicaoCurso?->curso,
                // 'instituicao' => $ct->instituicaoTutora,
                'instituicao' => $ct->instituicaoCurso?->instituicao,
                'podeEditar' => $ct->instituicaoCurso?->instituicao_id === $instituicaoId,
                'can' => [
                    'view_turmas' => $user->can('pauta.viewAnyCurso', $ct),
                ],
            ]),
            'anosLectivos' => AnoLectivo::all(),
            'anoLectivoActual' => $anoLectivoId,
        ]);
    }

    /**
     * Mostra a lista de turmas de um curso tutelado
     */
    public function indexTurmas(CursoTutelado $cursoTutelado)
    {
        $this->authorize('pauta.viewAnyCurso', $cursoTutelado);

        $cursoTutelado->load('instituicaoCurso.curso:id,nome');

        $user = Auth::user();
        $isProfessor = $user->hasRole('Professor');
        $professorId = $user->professor?->id;

        // Filtro ano lectivo
        $anoLectivoId = filled(request('ano_lectivo_id'))
            ? request('ano_lectivo_id')
            : $this->anoLectivoResolverService->obterAnoLectivoDefault();

        $turmas = Turma::whereHas(
            'cursoClasseTurno.cursoClasse',
            fn ($q) => $q->where('curso_tutelado_id', $cursoTutelado->id)
        )
            ->where('ano_lectivo_id', $anoLectivoId)  // ← Filtro ano lectivo
            ->when($isProfessor, fn ($q) => $q->whereHas(
                'professores',
                fn ($q2) => $q2->where('professor_id', $professorId)
            ))
            ->with([
                'cursoClasseTurno.cursoClasse.classe:id,nome',
                'cursoClasseTurno.turno:id,nome',
                'anoLectivo:id,nome',  // ← Carregue o ano
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
            'turmas' => $turmas->map(fn ($turma) => [
                'id' => $turma->id,
                'nome' => $turma->nome,
                'classe' => $turma->cursoClasseTurno?->cursoClasse?->classe?->nome,
                'turno' => $turma->cursoClasseTurno?->turno?->nome,
                'ano_lectivo' => $turma->anoLectivo?->nome,
                'can' => [
                    'view_pauta' => $user->can('pauta.view', $turma),
                ],
            ]),
            'anosLectivos' => AnoLectivo::all(),
            'anoLectivoActual' => $anoLectivoId,
        ]);
    }

    /**
     * Mostra a pauta da turma
     */
    public function pauta(CursoTutelado $cursoTutelado, Turma $turma, Request $request)
    {
        $this->authorize('pauta.view', $turma);

        $filtro = $request->query('filtro');

        $anoLectivoId = request('ano_lectivo_id') ?? $turma->ano_lectivo_id;

        abort_if(
            ! $turma->cursoClasseTurno?->cursoClasse?->where('curso_tutelado_id', $cursoTutelado->id)->exists(),
            404
        );

        abort_if($turma->ano_lectivo_id !== $anoLectivoId, 404);

        $periodo = $request->query('periodo', '1');

        $perPage = min((int) $request->query('per_page', 10), 100);

        $turma->load([
            'cursoClasseTurno.cursoClasse.classe:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'cursoClasseTurno.turno:id,nome',
            'anoLectivo:id,nome',
        ]);

        return Inertia::render('pautas/index', [
            'cursoTutelado' => $cursoTutelado->only('id'),
            'pauta' => $this->pautaService->gerarPauta($turma, $periodo, $perPage, $filtro),
            'periodo' => $periodo,
            'filtro' => $filtro,
            'anoLectivo' => $turma->anoLectivo,
        ]);
    }
}
