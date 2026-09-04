<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\CursoTutelado\CreateCursoTutelado;
use App\Actions\Tenant\CursoTutelado\DeleteCursoTutelado;
use App\Actions\Tenant\CursoTutelado\UpdateCursoTutelado;
use App\Actions\Tenant\CursoTutelado\UploadCursoTuteladoDocumentos;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CursoTutelado\StoreCursoTuteladoRequest;
use App\Http\Requests\Tenant\CursoTutelado\UpdateCursoTuteladoRequest;
use App\Http\Requests\Tenant\CursoTutelado\UploadCursoTuteladoDocumentosRequest;
use App\Http\Resources\Tenant\CursoTutelado\CursoTuteladoResourceEdit;
use App\Http\Resources\Tenant\CursoTutelado\CursoTuteladoResourceShow;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\User;
use App\Services\Tenant\AnoLectivo\AnoLectivoResolverService;
use App\Services\Tenant\CursoTuteladoViewService;
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
    public function index(Instituicao $instituicao)
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $cursos = $this->cursoTuteladoViewService->index($instituicao, $user);

        return Inertia::render('tenant/cursos-tutelados/index', [
            'cursos' => $cursos,
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

        return Inertia::render('tenant/cursos-tutelados/show', [
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
            ],
            'cursoTutelado' => (new CursoTuteladoResourceShow($cursoTutelado))->resolve(),
            'anoLectivoId' => $anoLectivoId,
            'anosLectivos' => $this->cursoTuteladoViewService->academicYears(),
            'can' => [
                'instituicao' => [
                    'view' => $user->can('view', $instituicao),
                ],
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

        $options = $this->cursoTuteladoViewService->editOptions($instituicao);

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
        Gate::authorize('update', $cursoTutelado);
        $this->uploadCursoTuteladoDocumentos->handle($cursoTutelado, $request->validated());

        return redirect()->route('tenant.dashboard.instituicoes.cursos-tutelados.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
        ])->with('toast', [
            'type' => 'success',
            'message' => 'Documentos actualizados com sucesso.',
        ]);
    }
}
