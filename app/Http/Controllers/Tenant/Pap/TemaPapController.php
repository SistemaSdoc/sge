<?php

namespace App\Http\Controllers\Tenant\Pap;

use App\Actions\Tenant\Pap\Tema\DefinirTemaPap;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Pap\Aprovacao\ActualizarTemaPapPelaCoordenacaoRequest;
use App\Http\Requests\Tenant\Pap\Tema\DefinirTemaPapRequest;
use App\Http\Resources\Tenant\Pap\Tema\TemaPapCriacaoResource;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Services\Tenant\Pap\Consultas\PrepararFormularioTemaPapService;
use App\Services\Tenant\Pap\Tema\TemaPapUnicidadeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TemaPapController extends Controller
{
    /**
     * Recebe o serviço de unicidade das propostas de tema.
     */
    public function __construct(
        private readonly TemaPapUnicidadeService $temaPapUnicidadeService,
        private readonly PrepararFormularioTemaPapService $prepararFormularioTemaPapService,
        private readonly DefinirTemaPap $definirTemaPap,
    ) {}

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

        $professores = $this->prepararFormularioTemaPapService
            ->professoresParaTema((string) $cursoTutelado->getKey());

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
            'form' => new TemaPapCriacaoResource((object) [
                'professores' => $professores,
                'sugestoes_temas' => $sugestoesTemas,
            ]),
        ]);
    }

    /**
     * Guarda a proposta de tema e notifica os seus destinatários.
     */
    public function store(
        DefinirTemaPapRequest $request,
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

        $validated = $request->validated();

        if ($validated['origem_tema'] === 'sugerido') {
            $sugestaoEscolhida = collect($cursoTuteladoDoGrupo->resolverSugestoesTemas())->first(
                fn ($sugestao) => (string) data_get($sugestao, 'id') === $validated['tema_sugerido_id']
            );
            $validated['tema_grupo'] = data_get($sugestaoEscolhida, 'titulo');
        }

        $campoTema = $validated['origem_tema'] === 'sugerido' ? 'tema_sugerido_id' : 'tema_grupo';

        unset($validated['origem_tema'], $validated['tema_sugerido_id']);

        $this->definirTemaPap->handle($grupoPap, $validated, $campoTema);

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
     * Apresenta o formulário para corrigir um tema com melhorias pedidas.
     */
    public function edit(GrupoPap $grupoPap): Response
    {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('atualizarTema', $grupoPap);

        $grupoPap->load([
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
            'professor.user',
        ]);

        return Inertia::render('tenant/pap/EditarTemaMelhoria', [
            'grupoPap' => $grupoPap,
            'routeContext' => 'instituicao',
            'routeParams' => [
                'grupoPap' => $grupoPap->id,
            ],
        ]);
    }

    /**
     * Actualiza o tema depois de uma melhoria solicitada.
     */
    public function update(
        ActualizarTemaPapPelaCoordenacaoRequest $request,
        GrupoPap $grupoPap
    ): RedirectResponse {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('atualizarTema', $grupoPap);

        $validated = $request->validated();

        $grupoPap->loadMissing('turma.cursoClasseTurno.cursoClasse');

        $turma = $grupoPap->turma;

        $this->temaPapUnicidadeService->validarUnicidade(
            $validated,
            (string) $turma?->cursoClasseTurno?->cursoClasse?->curso_tutelado_id,
            (string) $turma?->ano_lectivo_id,
            (string) $turma?->curso_classe_turno_id,
            (string) $grupoPap->getKey(),
        );

        $grupoPap->update($validated);

        return back()->with('success', 'Tema atualizado com sucesso.');
    }
}
