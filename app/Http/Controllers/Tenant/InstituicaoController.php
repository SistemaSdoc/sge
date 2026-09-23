<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Instituicao\CreateInstituicao;
use App\Actions\Tenant\Instituicao\DeleteInstituicao;
use App\Actions\Tenant\Instituicao\UpdateInstituicao;
use App\Enums\TutelaStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\InstituicoesRequest;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InstituicaoController extends Controller
{
    public function __construct(
        private readonly CreateInstituicao $createInstituicao,
        private readonly UpdateInstituicao $updateInstituicao,
        private readonly DeleteInstituicao $deleteInstituicao,
    ) {
        $this->authorizeResource(Instituicao::class, 'instituicao');
    }

    /**
     * Mostra a lista de instituições.
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        $instituicoes = Instituicao::select(['id', 'nome', 'sigla', 'tipo'])
            ->orderBy('nome', 'asc')
            ->paginate(10)
            ->through(function (Instituicao $instituicao) use ($user) {
                return [
                    'id' => $instituicao->id,
                    'nome' => $instituicao->nome,
                    'sigla' => $instituicao->sigla,
                    'tipo' => $instituicao->tipo,
                    'can' => [
                        'view' => $user->can('view', $instituicao),
                        'edit' => $user->can('update', $instituicao),
                        'delete' => $user->can('delete', $instituicao),
                    ],
                ];
            });

        return Inertia::render('tenant/instituicoes/index', [
            'can' => [
                'create' => $user->can('create', Instituicao::class),
            ],
            'instituicoes' => $instituicoes,
        ]);
    }

    /**
     * Mostra o formulário para criar uma instituição.
     */
    public function create()
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        return Inertia::render('tenant/instituicoes/create', [
            'can' => [
                'create' => $user->can('create', Instituicao::class),
            ],
        ]);
    }

    /**
     * Guarda uma nova instituição.
     */
    public function store(InstituicoesRequest $request)
    {
        $this->createInstituicao->handle($request->validated());

        return to_route('tenant.dashboard.instituicoes.index')->with('toast', [
            'type' => 'success',
            'message' => 'Instituição criada com sucesso!',
        ]);
    }

    /**
     * Mostra uma instituição e os seus cursos tutelados.
     */
    public function show(Instituicao $instituicao)
    {
        $cursos = $instituicao->instituicaoCursos()
            ->with([
                'curso:id,nome',
                'cursoTutelado.instituicaoTutora:id,nome',
                'cursoTutelado.cursoTuteladoShared:id,status,tenant_tutor_nome,tenant_tutor_id',
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(5)
            ->through(function ($instituicaoCurso) {
                $cursoTutelado = $instituicaoCurso->cursoTutelado;
                $sharedAtivo = $cursoTutelado
                    ? CursoTuteladoShared::on(config('tenancy.database.central_connection', config('database.default')))
                        ->where('curso_tutelado_tutelado_id', $cursoTutelado->getKey())
                        ->where('status', TutelaStatus::ACTIVO)
                        ->latest('updated_at')
                        ->first()
                    : null;
                $sharedPendente = $cursoTutelado
                    ? CursoTuteladoShared::on(config('tenancy.database.central_connection', config('database.default')))
                        ->where('curso_tutelado_tutelado_id', $cursoTutelado->getKey())
                        ->whereIn('status', [TutelaStatus::PENDENTE, TutelaStatus::PENDENTE_TROCA])
                        ->latest()
                        ->first()
                    : null;
                $conversaoPendente = $cursoTutelado && $sharedAtivo
                    ? User::query()->find(
                        Tenant::query()->find($sharedAtivo->tenant_tutelado_id)?->admin_user_id
                    )?->notifications()
                        ->whereIn('data->tipo', ['conversao_tutela_propria', 'conversao_tutela_propria_pendente'])
                        ->where('data->curso_tutelado_shared_id', (string) $sharedAtivo->getKey())
                        ->where('data->status', 'pendente')
                        ->exists()
                    : false;
                $sharedExibido = $sharedPendente?->status === TutelaStatus::PENDENTE
                    ? $sharedPendente
                    : $sharedAtivo;
                $nomeTutor = $sharedExibido?->tenant_tutor_nome
                    ?? $cursoTutelado?->instituicaoTutora?->nome
                    ?? $cursoTutelado?->cursoTuteladoShared?->tenant_tutor_nome;

                return [
                    'id' => $cursoTutelado->id,
                    'nome' => $instituicaoCurso->curso->nome,
                    'instituicao_tutora' => $nomeTutor
                        ?? $sharedExibido?->tenant_tutor_id
                        ?? $cursoTutelado?->instituicaoTutora?->id
                        ?? $cursoTutelado?->cursoTuteladoShared?->tenant_tutor_id,
                    'status' => $conversaoPendente
                        ? TutelaStatus::PENDENTE->value
                        : ($sharedPendente?->status?->value
                            ?? $sharedAtivo?->status?->value
                            ?? ($cursoTutelado?->tipo_tutela === 'propria' ? TutelaStatus::ACTIVO->value : null)),
                    'instituicao_tutora_pendente' => $sharedPendente?->status === TutelaStatus::PENDENTE_TROCA
                        ? $sharedPendente->tenant_tutor_nome
                        : null,
                    'can' => [
                        'view' => Auth::guard('tenant')->user()->can('view', $cursoTutelado),
                        'update' => Auth::guard('tenant')->user()->can('update', $cursoTutelado),
                        'delete' => Auth::guard('tenant')->user()->can('delete', $cursoTutelado),
                    ],
                ];
            });

        return Inertia::render('tenant/instituicoes/show', [
            'can' => [
                'edit' => Auth::guard('tenant')->user()->can('update', $instituicao),
                'create_curso' => Auth::guard('tenant')->user()->can('create', CursoTutelado::class),
                'view' => Auth::guard('tenant')->user()->can('view', $instituicao),
                'gerir_prazos' => Auth::guard('tenant')->user()->can('pautas.gerirPrazos'),
            ],
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
                'sigla' => $instituicao->sigla,
                'tipo' => $instituicao->tipo,
                'email' => $instituicao->email,
                'telefone' => $instituicao->telefone,
                'endereco' => $instituicao->endereco,
                'logo' => $instituicao->logo,
                'logo_url' => $instituicao->logo_url,
                'descricao' => $instituicao->descricao,
            ],
            'cursos' => $cursos,
        ]);
    }

    /**
     * Mostra o formulário para editar uma instituição.
     */
    public function edit(Instituicao $instituicao)
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        return Inertia::render('tenant/instituicoes/edit', [
            'can' => [
                'edit' => $user->can('update', $instituicao),
            ],
            'instituicao' => $instituicao,
            'logoUrl' => $instituicao->logo_url,
        ]);
    }

    /**
     * Actualiza uma instituição.
     */
    public function update(InstituicoesRequest $request, Instituicao $instituicao)
    {
        $this->updateInstituicao->handle($instituicao, $request->validated());

        return to_route('tenant.dashboard.instituicoes.show', $instituicao)->with('toast', [
            'type' => 'success',
            'message' => 'Instituição atualizada com sucesso!',
        ]);
    }

    /**
     * Remove uma instituição.
     */
    public function destroy(Instituicao $instituicao)
    {
        $this->deleteInstituicao->handle($instituicao);

        return to_route('tenant.dashboard.instituicoes.index')->with('toast', [
            'type' => 'success',
            'message' => 'Instituição excluída com sucesso!',
        ]);
    }
}
