<?php

namespace App\Http\Resources\Tenant\CursoTutelado;

use App\Enums\TutelaStatus;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Tenant\Inscricao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

class CursoTuteladoResourceShow extends JsonResource
{
    public function toArray(Request $request): array
    {
        $perPage = 5;
        $currentPageTurmas = $request->input('page_turmas', 1);
        $currentPageProfessores = $request->input('page_professores', 1);

        $turmasCollection = $this->cursoClasses
            ->flatMap(fn ($cc) => $cc->turnos)
            ->flatMap(fn ($cct) => $cct->turmas)
            ->map(fn ($turma) => [
                'id' => $turma->id,
                'nome' => $turma->nome,
                'max_alunos' => $turma->max_alunos,
                'curso_classe_turno_id' => $turma->cursoClasseTurno->id,
                'classe' => [
                    'id' => $turma->cursoClasseTurno->cursoClasse->id,
                    'nome' => $turma->cursoClasseTurno->cursoClasse->classe->nome,
                ],
                'turno' => [
                    'id' => $turma->cursoClasseTurno->turno->id,
                    'nome' => $turma->cursoClasseTurno->turno->nome,
                ],
            ]);

        $professoresCollection = $this->professores->map(fn ($prof) => [
            'id' => $prof->id,
            'vinculo_id' => $prof->pivot->id,
            'nome' => $prof->user?->nome,
            'tipo' => $prof->pivot->tipo,
            'coordenador' => (bool) $prof->pivot->coordenador,
            'opap' => (bool) $prof->pivot->opap,
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ]);

        $turmas = new LengthAwarePaginator(
            $turmasCollection->forPage($currentPageTurmas, $perPage)->values(),
            $turmasCollection->count(),
            $perPage,
            $currentPageTurmas,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $professores = new LengthAwarePaginator(
            $professoresCollection->forPage($currentPageProfessores, $perPage)->values(),
            $professoresCollection->count(),
            $perPage,
            $currentPageProfessores,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $sharedAtivo = $this->curso_tutelado_shared_id
            ? CursoTuteladoShared::on(config('tenancy.database.central_connection', config('database.default')))
                ->where('curso_tutelado_tutelado_id', $this->getKey())
                ->where('status', TutelaStatus::ACTIVO)
                ->latest('updated_at')
                ->first()
            : null;

        $sharedPendente = CursoTuteladoShared::on(config('tenancy.database.central_connection', config('database.default')))
            ->where('curso_tutelado_tutelado_id', $this->getKey())
            ->whereIn('status', [
                TutelaStatus::PENDENTE,
                TutelaStatus::PENDENTE_TROCA,
            ])
            ->latest()
            ->first();
        $sharedExibido = $sharedPendente?->status === TutelaStatus::PENDENTE
            ? $sharedPendente
            : $sharedAtivo;

        $docs = $this->resolverDocumentosPap();
        $sugestoesTemas = $this->tipo_tutela === 'externa'
            ? $this->resolverSugestoesTemas()
            : $this->sugestoesTemas;
        $sugestoesCollection = collect($sugestoesTemas)->map(fn ($sugestao) => [
            'id' => data_get($sugestao, 'id'),
            'titulo' => data_get($sugestao, 'titulo'),
            'descricao' => data_get($sugestao, 'descricao'),
            'ativo' => (bool) data_get($sugestao, 'ativo'),
        ]);
        $paginaSugestoes = min(
            max(1, (int) $request->input('page_sugestoes', 1)),
            max(1, (int) ceil($sugestoesCollection->count() / $perPage))
        );
        $sugestoes = new LengthAwarePaginator(
            $sugestoesCollection->forPage($paginaSugestoes, $perPage)->values(),
            $sugestoesCollection->count(),
            $perPage,
            $paginaSugestoes,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        $canManageSecretarios = ($request->user()?->hasRole('Coordenador') ?? false)
            && ($request->user()?->can('manageSecretarios', $this->resource) ?? false);

        return [
            'id' => $this->id,
            'curso' => [
                'nome' => $this->instituicaoCurso->curso->nome,
                'descricao' => $this->instituicaoCurso->curso->descricao,
                'duracao_anos' => $this->instituicaoCurso->duracao_anos,
            ],
            'instituicao' => [
                'id' => $this->instituicaoCurso->instituicao->id,
                'nome' => $this->instituicaoCurso->instituicao->nome,
            ],
            'tipo_tutela' => $this->tipo_tutela ?? 'propria',
            'instituicao_tutora' => $sharedExibido
                ? [
                    'id' => $sharedExibido->tenant_tutor_id,
                    'nome' => $sharedExibido->tenant_tutor_nome,
                ]
                : ($this->instituicaoTutora ? [
                    'id' => $this->instituicaoTutora->id,
                    'nome' => $this->instituicaoTutora->nome,
                ] : [
                    'id' => $this->cursoTuteladoShared?->tenant_tutor_id,
                    'nome' => $this->cursoTuteladoShared?->tenant_tutor_nome
                        ?? 'Instituição tutora externa',
                ]),
            'contadores' => [
                'turmas' => $turmasCollection->count(),
                'professores' => $professoresCollection->count(),
                'disciplinas' => $this->cursoClasses
                    ->flatMap(fn ($cc) => $cc->turnos)
                    ->flatMap(fn ($cct) => $cct->classeTurnoDisciplinas)
                    ->count(),
            ],
            'classes' => $this->cursoClasses->map(fn ($cc) => [
                'id' => $cc->id,
                'nome' => $cc->classe->nome,
                'turnos' => $cc->turnos->map(fn ($cct) => $cct->turno->nome),
            ]),
            'professores' => $professores->toArray(),
            'secretarios' => $canManageSecretarios ? $this->secretarios->map(fn ($secretario) => [
                'id' => $secretario->id,
                'nome' => $secretario->nome,
            ])->values() : [],
            'turmas' => $turmas->toArray(),
            'sugestoes_temas' => $sugestoes->toArray(),
            'criterios_pap_url' => $docs['criterios_pap_path']
                ? $this->publicStorageUrl($docs['criterios_pap_path'])
                : null,
            'manual_pt_url' => $docs['manual_pt_path']
                ? $this->publicStorageUrl($docs['manual_pt_path'])
                : null,
            'estrutura_trabalho_pap_url' => $docs['estrutura_trabalho_pap_path']
                ? $this->publicStorageUrl($docs['estrutura_trabalho_pap_path'])
                : null,
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
                'attachProfessor' => $request->user()?->can('update', $this->resource) ?? false,
                'manageSecretarios' => $canManageSecretarios,
                'attachSecretario' => $canManageSecretarios,
                'createInscricao' => ($request->user()?->can('create', Inscricao::class) ?? false)
                    && (! $request->user()?->hasRole('Secretario do Curso')
                        || $this->secretarios()->whereKey($request->user()->getKey())->exists()),
                'uploadCriteriosPap' => $request->user()?->can('uploadDocumentosPap', $this->resource) ?? false,
                'uploadManualPt' => $request->user()?->can('uploadDocumentosPap', $this->resource) ?? false,
                'uploadEstruturaTrabalhoPap' => $request->user()?->can('uploadDocumentosPap', $this->resource) ?? false,
            ],
        ];
    }

    private function publicStorageUrl(string $path): string
    {
        return route('tenant.storage', ['path' => $path]);
    }
}
