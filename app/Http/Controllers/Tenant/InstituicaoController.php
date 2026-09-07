<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\TutelaStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\InstituicoesRequest;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class InstituicaoController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Instituicao::class, 'instituicao', [
            'except' => [],
        ]);
    }

    public function index()
    {
        $instituicoes = Instituicao::select(['id', 'nome', 'sigla', 'tipo'])
            ->orderBy('nome', 'asc')
            ->paginate(10)
            ->through(function ($instituicao) {
                return [
                    'id' => $instituicao->id,
                    'nome' => $instituicao->nome,
                    'sigla' => $instituicao->sigla,
                    'tipo' => $instituicao->tipo,
                    'can' => [
                        'view_instituicao' => Auth::guard('tenant')->user()->can('view', $instituicao),
                        'edit_instituicao' => Auth::guard('tenant')->user()->can('update', $instituicao),
                        'delete_instituicao' => Auth::guard('tenant')->user()->can('delete', $instituicao),
                    ],
                ];
            });

        return Inertia::render('tenant/instituicoes/index', [
            'can' => [
                'create_instituicao' => Auth::guard('tenant')->user()->can('create', Instituicao::class),
            ],
            'instituicoes' => $instituicoes,
        ]);
    }

    public function create()
    {
        return Inertia::render('tenant/instituicoes/create', [
            'can' => [
                'create_instituicao' => Auth::guard('tenant')->user()->can('create', Instituicao::class),
            ],
        ]);
    }

    public function store(InstituicoesRequest $request)
    {
        $dados = $request->validated();

        if ($request->hasFile('logo')) {
            $dados['logo'] = $request->file('logo')->store('logos', 'public');
        }

        Instituicao::create($dados);

        return to_route('tenant.dashboard.instituicoes.index')->with('toast', [
            'type' => 'success',
            'message' => 'Instituição criada com sucesso!',
        ]);
    }

    public function show(Instituicao $instituicao)
    {
        $cursos = $instituicao->instituicaoCursos()
            ->with([
                'curso:id,nome',
                'cursoTutelado.instituicaoTutora:id,nome',
                'cursoTutelado.cursoTuteladoShared:id,status,tenant_tutor_nome,tenant_tutor_id',
            ])
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
                'edit_instituicao' => Auth::guard('tenant')->user()->can('update', $instituicao),
                'create_curso' => Auth::guard('tenant')->user()->can('create', CursoTutelado::class),
                'view_instituicao' => Auth::guard('tenant')->user()->can('view', $instituicao),
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

    public function edit(Instituicao $instituicao)
    {
        return Inertia::render('tenant/instituicoes/edit', [
            'can' => [
                'update_instituicao' => Auth::guard('tenant')->user()->can('update', $instituicao),
            ],
            'instituicao' => $instituicao,
            'logoUrl' => $instituicao->logo_url,
        ]);
    }

    public function update(InstituicoesRequest $request, Instituicao $instituicao)
    {
        $dados = $request->validated();

        if ($request->hasFile('logo')) {
            if ($instituicao->logo) {
                Storage::disk('public')->delete($instituicao->logo);
            }

            $dados['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $instituicao->update($dados);

        return to_route('tenant.dashboard.instituicoes.show', $instituicao)->with('toast', [
            'type' => 'success',
            'message' => 'Instituição atualizada com sucesso!',
        ]);
    }

    public function destroy(Instituicao $instituicao)
    {
        if ($instituicao->logo) {
            Storage::disk('public')->delete($instituicao->logo);
        }

        $instituicao->delete();

        return to_route('tenant.dashboard.instituicoes.index')->with('toast', [
            'type' => 'success',
            'message' => 'Instituição excluída com sucesso!',
        ]);
    }
}
