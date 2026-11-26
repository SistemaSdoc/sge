<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\CalendarioProva\CreateCalendarioProva;
use App\Actions\Tenant\CalendarioProva\DeleteCalendarioProva;
use App\Actions\Tenant\CalendarioProva\UpdateCalendarioProva;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CalendarioProva\StoreCalendarioProvaRequest;
use App\Http\Requests\Tenant\CalendarioProva\UpdateCalendarioProvaRequest;
use App\Models\Central\AnoLectivo;
use App\Models\Tenant\CalendarioProva;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CalendarioProvaController extends Controller
{
    public function __construct(
        private readonly CreateCalendarioProva $createCalendarioProva,
        private readonly UpdateCalendarioProva $updateCalendarioProva,
        private readonly DeleteCalendarioProva $deleteCalendarioProva,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', CalendarioProva::class);

        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        $instituicaoId = $user->instituicaoFiltro();

        // Só filtra por turma se o utilizador for Aluno
        $cursoClasseTurnoId = null;
        if ($user->hasRole('Aluno')) {
            $cursoClasseTurnoId = $user->aluno
                ?->turmaActual()
                ->first()
                ?->cursoClasseTurno
                ?->id;
        }

        $anoLectivoId = $request->string('ano_lectivo_id')->toString()
            ?: AnoLectivo::activo()?->id;

        $calendarios = CalendarioProva::with([
            'cursoClasseTurno.cursoClasse.classe:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'cursoClasseTurno.turno:id,nome',
            'anoLectivo:id,nome',
        ])
            ->withCount('entradas')
            ->whereHas(
                'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
                fn ($q) => $q->where('instituicao_id', $instituicaoId)
            )
            ->when(
                $cursoClasseTurnoId,
                fn ($q) => $q->where('curso_classe_turno_id', $cursoClasseTurnoId)
            )
            ->when(
                ! $user->can('create', CalendarioProva::class),
                fn ($q) => $q->where('ativo', true)
            )
            ->doAnoLectivo($anoLectivoId)
            ->when(
                $request->string('tipo')->toString(),
                fn ($q, $tipo) => $q->doTipo($tipo)
            )
            ->orderBy('tipo')
            ->orderBy('trimestre')
            ->paginate(10)
            ->withQueryString();

        $calendarios->getCollection()->transform(function ($calendario) use ($user) {
            return [
                ...$calendario->toArray(),
                'can' => [
                    'view'   => $user->can('view', $calendario),
                    'update' => $user->can('update', $calendario),
                ],
            ];
        });

        return Inertia::render('tenant/calendario-provas/index', [
            'calendarios'      => $calendarios,
            'anosLectivos'     => AnoLectivo::all(['id', 'nome']),
            'anoLectivoId'     => $anoLectivoId,
            'filters'          => [
                'tipo'           => $request->string('tipo')->toString(),
                'ano_lectivo_id' => $anoLectivoId,
            ],
            'can'              => [
                'create' => $user->can('create', CalendarioProva::class),
            ],
            'anoLectivoActivo' => AnoLectivo::activo()?->id === $anoLectivoId,
        ]);
    }

    public function create()
    {
        $this->authorize('create', CalendarioProva::class);

        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $cursos = $this->estruturarCursos(
            instituicaoId: $user->instituicaoFiltro(),
            anoLectivoId: AnoLectivo::activo()?->id,
        );

        return Inertia::render('tenant/calendario-provas/create', [
            'cursos'           => $cursos,
            'anosLectivos'     => AnoLectivo::all(['id', 'nome']),
            'anoLectivoActivo' => AnoLectivo::activo()?->only('id', 'nome'),
        ]);
    }

    public function store(StoreCalendarioProvaRequest $request): RedirectResponse
    {
        $this->authorize('create', CalendarioProva::class);

        $this->createCalendarioProva->handle($request->validated());

        return to_route('tenant.dashboard.calendario-provas.index')->with('toast', [
            'type'    => 'success',
            'message' => 'Calendário de provas criado com sucesso.',
        ]);
    }

    public function show(CalendarioProva $calendarioProva)
    {
        $this->authorize('view', $calendarioProva);

        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $calendarioProva->load([
            'cursoClasseTurno.cursoClasse.classe:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'cursoClasseTurno.turno:id,nome',
            'anoLectivo:id,nome',
            'entradas.disciplina:id,nome,sigla',
        ]);

        return Inertia::render('tenant/calendario-provas/show', [
            'calendario' => $calendarioProva,
            'can'        => [
                'update'      => $user->can('update', $calendarioProva),
                'delete'      => $user->can('delete', $calendarioProva),
                'toggleAtivo' => $user->can('toggleAtivo', $calendarioProva),
            ],
        ]);
    }

    public function edit(CalendarioProva $calendarioProva)
    {
        $this->authorize('update', $calendarioProva);

        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $calendarioProva->load([
            'cursoClasseTurno.cursoClasse.classe:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'cursoClasseTurno.turno:id,nome',
            'anoLectivo:id,nome',
            'entradas.disciplina:id,nome,sigla',
        ]);

        $cursos = $this->estruturarCursos(
            instituicaoId: $user->instituicaoFiltro(),
            anoLectivoId: $calendarioProva->ano_lectivo_id,
        );

        return Inertia::render('tenant/calendario-provas/edit', [
            'calendario'   => $calendarioProva,
            'cursos'       => $cursos,
            'anosLectivos' => AnoLectivo::all(['id', 'nome']),
        ]);
    }

    public function update(UpdateCalendarioProvaRequest $request, CalendarioProva $calendarioProva): RedirectResponse
    {
        $this->authorize('update', $calendarioProva);

        $this->updateCalendarioProva->handle($calendarioProva, $request->validated());

        return to_route('tenant.dashboard.calendario-provas.index')->with('toast', [
            'type'    => 'success',
            'message' => 'Calendário de provas actualizado com sucesso.',
        ]);
    }

    public function toggleAtivo(CalendarioProva $calendarioProva): RedirectResponse
    {
        $this->authorize('toggleAtivo', $calendarioProva);

        $calendarioProva->update(['ativo' => ! $calendarioProva->ativo]);

        return back()->with('toast', [
            'type'    => 'success',
            'message' => $calendarioProva->ativo
                ? 'Calendário publicado com sucesso.'
                : 'Calendário despublicado com sucesso.',
        ]);
    }

    public function destroy(CalendarioProva $calendarioProva): RedirectResponse
    {
        $this->authorize('delete', $calendarioProva);

        $this->deleteCalendarioProva->handle($calendarioProva);

        return to_route('tenant.dashboard.calendario-provas.index')->with('toast', [
            'type'    => 'success',
            'message' => 'Calendário de provas removido com sucesso.',
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers privados
    // -------------------------------------------------------------------------

    /**
     * Carrega os CursoClasseTurnos da instituição e estrutura-os
     * em cursos → classes → turnos para o frontend.
     */
    private function estruturarCursos(string $instituicaoId, ?string $anoLectivoId): \Illuminate\Support\Collection
    {
        $cursoClasseTurnos = CursoClasseTurno::with([
            'cursoClasse.classe:id,nome',
            'cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'turno:id,nome',
            'classeTurnoDisciplinas' => fn ($q) => $q->where('ano_lectivo_id', $anoLectivoId),
            'classeTurnoDisciplinas.disciplina:id,nome,sigla',
        ])
            ->whereHas(
                'cursoClasse.cursoTutelado.instituicaoCurso',
                fn ($q) => $q->where('instituicao_id', $instituicaoId)
            )
            ->get();

        return $cursoClasseTurnos
            ->groupBy(fn ($cct) => $cct->cursoClasse->cursoTutelado->instituicaoCurso->curso->id)
            ->map(fn ($grupo) => [
                'id'      => $grupo->first()->cursoClasse->cursoTutelado->instituicaoCurso->curso->id,
                'nome'    => $grupo->first()->cursoClasse->cursoTutelado->instituicaoCurso->curso->nome,
                'classes' => $grupo
                    ->groupBy(fn ($cct) => $cct->cursoClasse->classe->id)
                    ->map(fn ($classeGrupo) => [
                        'id'     => $classeGrupo->first()->cursoClasse->classe->id,
                        'nome'   => $classeGrupo->first()->cursoClasse->classe->nome,
                        'turnos' => $classeGrupo->map(fn ($cct) => [
                            'id'                       => $cct->id,
                            'nome'                     => $cct->turno->nome,
                            'classe_turno_disciplinas' => $cct->classeTurnoDisciplinas,
                        ])->values(),
                    ])->values(),
            ])->values();
    }
}