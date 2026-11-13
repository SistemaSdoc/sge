<?php

namespace App\Http\Controllers\Tenant\Pap;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\GrupoPap\ActualizarTemaRequest;
use App\Http\Resources\Tenant\GrupoPap\ShowResource;
use App\Http\Resources\Tenant\GrupoPap\TemaCreateResource;
use App\Models\Central\AnoLectivo;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use App\Notifications\Pap\TemaDefinidoNotification;
use App\Rules\EstudoCasoPapUnico;
use App\Rules\TemaPapUnico;
use App\Services\Tenant\TemaPapUnicidadeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TemaPapController extends Controller
{
    /**
     * Recebe o serviço de unicidade das propostas de tema.
     */
    public function __construct(private readonly TemaPapUnicidadeService $temaPapUnicidadeService) {}

    /**
     * Apresenta o formulário para definir a proposta de tema do grupo.
     */
    public function create(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('definirTema', $grupoPap);
        $cursoTuteladoDoGrupo = $grupoPap->turma?->cursoClasseTurno?->cursoClasse?->cursoTutelado;
        abort_unless($cursoTuteladoDoGrupo?->is($cursoTutelado), 404);
        $anoLectivoId = $turma->ano_lectivo_id;

        $professores = Professor::whereHas('cursosTutelados', function ($query) use ($cursoTutelado) {
            $query->where('curso_tutelado_id', $cursoTutelado->id)
                ->where('opap', true);
        })->with('user:id,nome')->get();
        $titulosUsados = $this->temaPapUnicidadeService->titulosUsadosNoTurno(
            (string) $cursoTuteladoDoGrupo->getKey(),
            (string) $anoLectivoId,
            (string) $cursoClasseTurno->getKey(),
            (string) $grupoPap->getKey(),
        );
        $sugestoesTemas = collect($cursoTuteladoDoGrupo->resolverSugestoesTemas())
            ->filter(fn ($sugestao) => (bool) data_get($sugestao, 'ativo', true))
            ->reject(fn ($sugestao) => $titulosUsados->contains(
                $this->temaPapUnicidadeService->normalizarTitulo((string) data_get($sugestao, 'titulo'))
            ))
            ->values();

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/pap/tema/create', [
            'instituicao' => $instituicao->only('id', 'nome'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id', 'nome'),
            'anoLectivoId' => $anoLectivoId,
            'grupoPap' => $grupoPap->only('id'),
            'form' => new TemaCreateResource((object) [
                'professores' => $professores,
                'sugestoes_temas' => $sugestoesTemas,
            ]),
        ]);
    }

    /**
     * Guarda a proposta de tema e notifica os seus destinatários.
     */
    public function store(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('definirTema', $grupoPap);
        $cursoTuteladoDoGrupo = $grupoPap->turma?->cursoClasseTurno?->cursoClasse?->cursoTutelado;
        abort_unless($cursoTuteladoDoGrupo?->is($cursoTutelado), 404);
        $titulosUsados = $this->temaPapUnicidadeService->titulosUsadosNoTurno(
            (string) $cursoTuteladoDoGrupo->getKey(),
            (string) $turma->ano_lectivo_id,
            (string) $cursoClasseTurno->getKey(),
            (string) $grupoPap->getKey(),
        );
        $sugestoesTemas = collect($cursoTuteladoDoGrupo->resolverSugestoesTemas())
            ->filter(fn ($sugestao) => (bool) data_get($sugestao, 'ativo', true))
            ->reject(fn ($sugestao) => $titulosUsados->contains(
                $this->temaPapUnicidadeService->normalizarTitulo((string) data_get($sugestao, 'titulo'))
            ))
            ->values();
        $idsSugestoes = $sugestoesTemas
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $validated = $request->validate([
            'origem_tema' => ['required', Rule::in(['sugerido', 'autoral'])],
            'tema_sugerido_id' => [
                'nullable',
                'required_if:origem_tema,sugerido',
                'string',
                Rule::in($idsSugestoes),
            ],
            'tema_grupo' => [
                Rule::requiredIf($request->input('origem_tema') === 'autoral'),
                'nullable',
                'string',
                'max:255',
                new TemaPapUnico(
                    (string) $cursoTuteladoDoGrupo->getKey(),
                    (string) $turma->ano_lectivo_id,
                    (string) $cursoClasseTurno->getKey(),
                    (string) $grupoPap->id,
                ),
            ],
            'professor_tutor_id' => ['required', 'exists:professores,id'],
            'problema' => ['required', 'string', 'max:1000'],
            'objectivo_geral' => ['required', 'string', 'max:1000'],
            'objectivos_especificos' => ['required', 'string', 'max:1000'],
            'estudo_caso' => [
                'required',
                'string',
                'max:1000',
                new EstudoCasoPapUnico(
                    (string) $cursoTuteladoDoGrupo->getKey(),
                    (string) $turma->ano_lectivo_id,
                    (string) $cursoClasseTurno->getKey(),
                    $request->input('tema_grupo'),
                    (string) $grupoPap->id,
                ),
            ],
        ], [
            'tema_grupo.required' => 'Indique o tema proposto pelo grupo.',
            'professor_tutor_id.required' => 'Seleccione o professor tutor do grupo.',
            'professor_tutor_id.exists' => 'O professor tutor seleccionado não existe.',
            'problema.required' => 'Descreva o problema que o trabalho pretende resolver.',
            'objectivo_geral.required' => 'Indique o objectivo geral do trabalho.',
            'objectivos_especificos.required' => 'Indique os objectivos específicos do trabalho.',
            'estudo_caso.required' => 'Descreva o estudo de caso do trabalho.',
        ]);

        if ($validated['origem_tema'] === 'sugerido') {
            $sugestaoEscolhida = $sugestoesTemas->first(
                fn ($sugestao) => (string) data_get($sugestao, 'id') === $validated['tema_sugerido_id']
            );
            $validated['tema_grupo'] = data_get($sugestaoEscolhida, 'titulo');
        }

        $campoErroTema = $validated['origem_tema'] === 'sugerido'
            ? 'tema_sugerido_id'
            : 'tema_grupo';
        $this->temaPapUnicidadeService->validarUnicidade(
            $validated,
            (string) $cursoTuteladoDoGrupo->getKey(),
            (string) $turma->ano_lectivo_id,
            (string) $cursoClasseTurno->getKey(),
            (string) $grupoPap->getKey(),
            $campoErroTema,
        );
        unset($validated['origem_tema'], $validated['tema_sugerido_id']);
        $grupoPap->update([
            ...$validated,
            'status_aprovacao' => GrupoPap::APROVACAO_SUBMETIDO,
        ]);

        $alunosUsers = $grupoPap->alunos->map->user->filter();
        $tutorUser = $grupoPap->professor?->user;
        $destinatarios = $alunosUsers;

        if ($tutorUser) {
            $destinatarios = $destinatarios->push($tutorUser)->unique('id');
        }

        Notification::send($destinatarios, new TemaDefinidoNotification($grupoPap));

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,
        ])->with('toast', [
            'type' => 'success',
            'message' => 'Proposta do grupo PAP criada com sucesso!',
        ]);
    }

    /**
     * Apresenta o formulário de edição da proposta do grupo.
     */
    public function edit(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('definirTema', $grupoPap);

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/pap/tema/edit', [
            'instituicao' => $instituicao->only('id', 'nome'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id', 'nome'),
            'anoLectivoId' => $turma->ano_lectivo_id,
            'grupoPap' => new ShowResource($grupoPap),
        ]);
    }

    /**
     * Actualiza a proposta de tema submetida pelo grupo.
     */
    public function update(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('definirTema', $grupoPap);
        $validated = $request->validate([
            'tema_grupo' => [
                'required',
                'string',
                'max:255',
                new TemaPapUnico(
                    (string) $cursoTutelado->getKey(),
                    (string) $turma->ano_lectivo_id,
                    (string) $cursoClasseTurno->getKey(),
                    (string) $grupoPap->getKey(),
                ),
            ],
            'professor_tutor_id' => ['required', 'exists:professores,id'],
            'problema' => ['required', 'string', 'max:1000'],
            'objectivo_geral' => ['required', 'string', 'max:1000'],
            'objectivos_especificos' => ['required', 'string', 'max:1000'],
            'estudo_caso' => [
                'required',
                'string',
                'max:1000',
                new EstudoCasoPapUnico(
                    (string) $cursoTutelado->getKey(),
                    (string) $turma->ano_lectivo_id,
                    (string) $cursoClasseTurno->getKey(),
                    $request->input('tema_grupo', $grupoPap->tema_grupo),
                    (string) $grupoPap->getKey(),
                ),
            ],
        ], [
            'professor_tutor_id.required' => 'Seleccione o professor tutor do grupo.',
            'professor_tutor_id.exists' => 'O professor tutor seleccionado não existe.',
            'problema.required' => 'Descreva o problema que o trabalho pretende resolver.',
            'objectivo_geral.required' => 'Indique o objectivo geral do trabalho.',
            'objectivos_especificos.required' => 'Indique os objectivos específicos do trabalho.',
            'estudo_caso.required' => 'Descreva o estudo de caso do trabalho.',
        ]);
        $this->temaPapUnicidadeService->validarUnicidade(
            $validated,
            (string) $cursoTutelado->getKey(),
            (string) $turma->ano_lectivo_id,
            (string) $cursoClasseTurno->getKey(),
            (string) $grupoPap->getKey(),
        );
        $grupoPap->update([
            ...$validated,
            'status_aprovacao' => GrupoPap::APROVACAO_SUBMETIDO,
        ]);

        $alunosUsers = $grupoPap->alunos->map->user->filter();
        $tutorUser = $grupoPap->professor?->user;
        $destinatarios = $alunosUsers;

        if ($tutorUser) {
            $destinatarios = $destinatarios->push($tutorUser)->unique('id');
        }

        Notification::send($destinatarios, new TemaDefinidoNotification($grupoPap));

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,
        ])->with('toast', [
            'type' => 'success',
            'message' => 'Proposta do grupo PAP actualizada com sucesso!',
        ]);
    }

    /**
     * Apresenta o formulário para corrigir o tema do grupo.
     */
    public function editarTema(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('corrigirTema', $grupoPap);

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/pap/editar-tema', [
            'instituicao' => $instituicao->only('id', 'nome'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id', 'nome'),
            'anoLectivoId' => $turma->ano_lectivo_id,
            'anosLectivos' => AnoLectivo::all(),
            'grupoPap' => new ShowResource($grupoPap),
        ]);
    }

    /**
     * Guarda a correcção do tema e devolve o grupo ao tutor.
     */
    public function actualizarTema(
        ActualizarTemaRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('corrigirTema', $grupoPap);
        $validated = $request->validated();
        $grupoPap->update([
            ...$validated,
            'status_aprovacao' => GrupoPap::APROVACAO_SUBMETIDO,
        ]);

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,
        ])->with('toast', [
            'type' => 'success',
            'message' => 'Tema corrigido. Aguarda revisão do professor tutor.',
        ]);
    }
}
