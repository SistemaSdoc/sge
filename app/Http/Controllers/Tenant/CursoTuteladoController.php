<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\CursoTutelado\CreateCursoTutelado;
use App\Actions\Tenant\CursoTutelado\DeleteCursoTutelado;
use App\Actions\Tenant\CursoTutelado\UpdateCursoTutelado;
use App\Actions\Tenant\CursoTutelado\UploadCursoTuteladoDocumentos;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CursoTutelado\StoreCursoTuteladoRequest;
use App\Http\Requests\Tenant\CursoTutelado\StoreSugestaoTemaPapRequest;
use App\Http\Requests\Tenant\CursoTutelado\UpdateCursoTuteladoRequest;
use App\Http\Requests\Tenant\CursoTutelado\UpdateSugestaoTemaPapRequest;
use App\Http\Requests\Tenant\CursoTutelado\UploadCursoTuteladoDocumentosRequest;
use App\Http\Resources\Tenant\CursoTutelado\CursoTuteladoResourceEdit;
use App\Http\Resources\Tenant\CursoTutelado\CursoTuteladoResourceShow;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\SugestaoTemaPap;
use App\Models\Tenant\User;
use App\Services\Tenant\AnoLectivo\AnoLectivoResolverService;
use App\Services\Tenant\CursoTuteladoViewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Orquestra as operações e respostas HTTP dos cursos tutelados.
 */
class CursoTuteladoController extends Controller
{
    public function __construct(
        private readonly AnoLectivoResolverService $anoLectivoResolverService,
        private readonly CursoTuteladoViewService $cursoTuteladoViewService,
        private readonly CreateCursoTutelado $createCursoTutelado,
        private readonly UpdateCursoTutelado $updateCursoTutelado,
        private readonly DeleteCursoTutelado $deleteCursoTutelado,
        private readonly UploadCursoTuteladoDocumentos $uploadCursoTuteladoDocumentos,
    ) {}

    /**
     * Apresenta os cursos tutelados de uma instituição.
     */
    public function index(Request $request, Instituicao $instituicao)
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        if ($user->hasRole('Secretario do Curso')) {
            abort_unless((string) $user->instituicao_id === (string) $instituicao->getKey(), 404);
            Gate::authorize('viewAny', CursoTutelado::class);
        }

        $cursos = $this->cursoTuteladoViewService->index(
            $instituicao,
            $user,
            $request->string('search')->toString(),
        );

        return Inertia::render('tenant/cursos-tutelados/index', [
            'cursos' => $cursos,
            'filters' => $request->only('search'),
            'instituicao' => $instituicao->only('id'),
            'can' => [
                'create_curso' => $user->can('create', CursoTutelado::class),
            ],
        ]);
    }

    /**
     * Apresenta o formulário de criação de um curso tutelado.
     */
    public function create(Instituicao $instituicao)
    {
        Gate::authorize('create', CursoTutelado::class);

        $options = $this->cursoTuteladoViewService->createOptions($instituicao);

        return Inertia::render('tenant/cursos-tutelados/create', [
            'instituicao' => $instituicao->only('id', 'nome', 'tipo'),
            ...$options,
        ]);
    }

    /**
     * Cria um curso tutelado e redirecciona para a listagem.
     */
    public function store(
        StoreCursoTuteladoRequest $request,
        Instituicao $instituicao
    ) {
        Gate::authorize('create', CursoTutelado::class);

        $validated = $request->validated();

        $this->createCursoTutelado->handle($instituicao, $validated);

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.index', $instituicao)->with('toast', [
            'type' => 'success',
            'message' => 'Curso criado com sucesso!',
        ]);
    }

    /**
     * Lista os cursos centrais oferecidos por um instituto tutor.
     */
    public function cursosDisponiveis(Request $request, Instituicao $instituicao): array
    {
        return [
            'data' => $this->cursoTuteladoViewService->cursosDisponiveisParaTutor(
                (string) $request->query('tenant_tutor_id'),
                $instituicao,
            ),
        ];
    }

    /**
     * Retorna os detalhes (nível de ensino e classes) de um curso no instituto tutor.
     */
    public function cursoDetalhes(Request $request, Instituicao $instituicao): array
    {
        return [
            'data' => $this->cursoTuteladoViewService->detalhesCursoTutor(
                (string) $request->query('tenant_tutor_id'),
                (string) $request->query('curso_id'),
                $instituicao,
            ),
        ];
    }

    /**
     * Apresenta o detalhe de um curso tutelado.
     */
    public function show(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado
    ) {
        Gate::authorize('view', $cursoTutelado);

        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $anoLectivoId = filled(request('ano_lectivo_id'))
            ? request('ano_lectivo_id')
            : $this->anoLectivoResolverService->obterAnoLectivoDefault();

        $this->cursoTuteladoViewService->prepareShow($cursoTutelado, $anoLectivoId);

        $canManageSecretarios = $user->hasRole('Coordenador')
            && $user->can('manageSecretarios', $cursoTutelado);

        $secretariosDisponiveis = $canManageSecretarios
            ? User::query()
                ->where('instituicao_id', $instituicao->id)
                ->role('Secretario do Curso', 'tenant')
                ->whereDoesntHave('roles', fn ($query) => $query->whereIn('name', [
                    'Secretaria',
                    'Director',
                    'Subdirector',
                    'Coordenador',
                    'SuperAdmin',

                ]))
                ->whereDoesntHave('cursosSecretariados', fn ($query) => $query->whereKey($cursoTutelado->getKey()))
                ->orderBy('nome')
                ->get(['id', 'nome', 'email'])
                ->map(fn (User $candidate): array => [
                    'id' => $candidate->id,
                    'nome' => $candidate->nome,
                    'email' => $candidate->email,
                ])
            : collect();

        return Inertia::render('tenant/cursos-tutelados/show', [
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
            ],
            'cursoTutelado' => (new CursoTuteladoResourceShow($cursoTutelado))->resolve(),
            'secretariosDisponiveis' => $secretariosDisponiveis,
            'anoLectivoId' => $anoLectivoId,
            'anosLectivos' => $this->cursoTuteladoViewService->academicYears(),
            'can' => [
                'instituicao' => [
                    'view' => $user->can('view', $instituicao),
                ],
                'uploadCriteriosPap' => $user->can('uploadDocumentosPap', $cursoTutelado),
            ],
        ]);
    }

    /**
     * Apresenta o formulário de edição de um curso tutelado.
     */
    public function edit(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado
    ) {
        Gate::authorize('update', $cursoTutelado);

        $this->cursoTuteladoViewService->prepareEdit($cursoTutelado);

        $options = $this->cursoTuteladoViewService->editOptions($instituicao, $cursoTutelado);

        return Inertia::render('tenant/cursos-tutelados/edit', [
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
                'tipo' => $instituicao->tipo,
            ],
            'cursoTutelado' => (new CursoTuteladoResourceEdit($cursoTutelado))->resolve(),
            ...$options,
        ]);
    }

    /**
     * Actualiza um curso tutelado e a sua tutela.
     */
    public function update(
        UpdateCursoTuteladoRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado
    ) {
        Gate::authorize('update', $cursoTutelado);

        $this->updateCursoTutelado->handle($instituicao, $cursoTutelado, $request->validated());

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.index', $instituicao)->with('toast', [
            'type' => 'success',
            'message' => 'Curso tutelado atualizado com sucesso!',
        ]);
    }

    /**
     * Remove um curso tutelado sem turmas associadas.
     */
    public function destroy(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado
    ) {
        Gate::authorize('update', $cursoTutelado);

        $this->deleteCursoTutelado->handle($cursoTutelado);

        return response()->noContent();
    }

    /**
     * Guarda os documentos PAP do curso tutelado.
     */
    public function uploadCriteriosPap(
        UploadCursoTuteladoDocumentosRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado
    ) {
        // dd($request->validated());
        Gate::authorize('uploadDocumentosPap', $cursoTutelado);
        $this->uploadCursoTuteladoDocumentos->handle($cursoTutelado, $request->validated());

        return redirect()->route('tenant.dashboard.instituicoes.cursos-tutelados.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
        ])->with('toast', [
            'type' => 'success',
            'message' => 'Documentos actualizados com sucesso.',
        ]);
    }

    private function garantirTutelaPropria(CursoTutelado $cursoTutelado): void
    {
        abort_if(
            $cursoTutelado->tipo_tutela === 'externa',
            403,
            'As sugestões deste curso são geridas pela instituição tutora.'
        );
    }

    public function storeSugestaoTema(StoreSugestaoTemaPapRequest $request, Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        Gate::authorize('uploadDocumentosPap', $cursoTutelado);
        $this->garantirTutelaPropria($cursoTutelado);
        $cursoTutelado->sugestoesTemas()->create($request->validated());

        return back()->with('toast', ['type' => 'success', 'message' => 'Sugestão cadastrada.']);
    }

    public function updateSugestaoTema(
        UpdateSugestaoTemaPapRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        SugestaoTemaPap $sugestao
    ) {
        Gate::authorize('uploadDocumentosPap', $cursoTutelado);
        $this->garantirTutelaPropria($cursoTutelado);
        abort_unless($sugestao->curso_tutelado_id === $cursoTutelado->id, 404);

        $sugestao->update($request->validated());

        return back()->with('toast', ['type' => 'success', 'message' => 'Sugestão actualizada.']);
    }

    public function destroySugestaoTema(Instituicao $instituicao, CursoTutelado $cursoTutelado, SugestaoTemaPap $sugestao)
    {
        Gate::authorize('uploadDocumentosPap', $cursoTutelado);
        $this->garantirTutelaPropria($cursoTutelado);
        abort_unless($sugestao->curso_tutelado_id === $cursoTutelado->id, 404);

        $sugestao->delete();

        return back()->with('toast', ['type' => 'success', 'message' => 'Sugestão removida.']);
    }
}
