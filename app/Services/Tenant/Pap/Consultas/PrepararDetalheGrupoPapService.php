<?php

namespace App\Services\Tenant\Pap\Consultas;

use App\Helpers\PapHelper;
use App\Models\Central\Tenant;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PrepararDetalheGrupoPapService
{
    /**
     * Carrega as relações necessárias para apresentar o detalhe do grupo.
     */
    public function prepararDetalhe(GrupoPap $grupoPap): void
    {
        $grupoPap->load([
            'professor.user:id,nome,email',
            'historicoAprovacao.utilizador:id,nome,instituicao_id',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
        ]);
    }

    /**
     * Devolve os membros da banca e os elementos do grupo em páginas separadas.
     *
     * @return array{banca: LengthAwarePaginator, elementos: LengthAwarePaginator}
     */
    public function detalhesPaginados(GrupoPap $grupoPap): array
    {
        return [
            'banca' => $grupoPap->jurados()
                ->with('professor.user:id,nome,email')
                ->orderByDesc('created_at')
                ->paginate(10, ['*'], 'page_banca'),
            'elementos' => $grupoPap->elementos()
                ->with([
                    'aluno.inscricao.candidato:id,nome,email',
                    'aluno:id,user_id,matricula,inscricao_id',
                    'aluno.user:id,avatar',
                ])
                ->latest('created_at')
                ->paginate(10, ['*'], 'page_elementos'),
        ];
    }

    /**
     * Prepara o trabalho PAP, as versões e os feedbacks para a página do grupo.
     *
     * @return array<string, mixed>|null
     */
    public function detalhesTrabalho(
        GrupoPap $grupoPap,
        ?Instituicao $instituicaoTutora,
        ?string $nomeCurso,
        ?string $siglaTutora,
    ): ?array {
        $trabalho = $grupoPap->trabalhoPap()->with([
            'versoes.submetidoPor:id,nome',
            'versoes.feedbacks.utilizador:id,nome,instituicao_id',
            'aprovadoPor:id,nome,instituicao_id',
        ])->first();

        if (! $trabalho) {
            return null;
        }

        return [
            'id' => $trabalho->id,
            'status' => $trabalho->status,
            'data_aprovacao' => $trabalho->data_aprovacao?->toIso8601String(),
            'aprovado_por' => $this->rotuloDecisor(
                $trabalho->aprovadoPor,
                $trabalho->aprovado_por_externo_tenant_id,
                $instituicaoTutora,
                $siglaTutora,
                $nomeCurso,
            ),
            'versoes' => $trabalho->versoes->map(fn ($versao): array => [
                'id' => $versao->id,
                'numero_versao' => $versao->numero_versao,
                'nome_original' => $versao->nome_original,
                'status_quando_submetido' => $versao->status_quando_submetido,
                'submetido_por' => $versao->submetidoPor?->nome,
                'created_at' => $versao->created_at?->toIso8601String(),
                'feedbacks' => $versao->feedbacks->map(fn ($feedback): array => [
                    'id' => $feedback->id,
                    'tipo' => $feedback->tipo,
                    'comentario' => $feedback->comentario,
                    'utilizador' => in_array($feedback->tipo, [
                        'correcao_coordenacao',
                        'aprovacao_coordenacao',
                        'reprovacao_coordenacao',
                    ], true)
                        ? $this->rotuloDecisor(
                            $feedback->utilizador,
                            $feedback->utilizador_externo_tenant_id,
                            $instituicaoTutora,
                            $siglaTutora,
                            $nomeCurso,
                        )
                        : $feedback->utilizador?->nome,
                    'created_at' => $feedback->created_at?->toIso8601String(),
                    'tem_ficheiro_correcao' => $feedback->caminho_ficheiro_correcao !== null,
                    'nome_original_correcao' => $feedback->nome_original_correcao,
                ])->values(),
            ])->values(),
        ];
    }

    /**
     * Prepara o histórico de aprovação e identifica os decisores.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function historico(
        GrupoPap $grupoPap,
        ?string $instituicaoTutoraId,
        ?string $nomeCurso,
        ?string $siglaInstituto,
    ): Collection {
        return $grupoPap->historicoAprovacao->map(function ($item) use (
            $instituicaoTutoraId,
            $nomeCurso,
            $siglaInstituto,
        ): array {
            $mostrarNomeDoUtilizador = in_array($item->estado_novo, [
                GrupoPap::APROVACAO_MELHORIA_TUTOR,
                GrupoPap::APROVACAO_SUBMETIDO,
            ], true);
            $ehTutora = $item->estado_novo !== 'pendente' && (
                $item->utilizador_externo_tenant_id !== null
                || ($instituicaoTutoraId !== null && $item->utilizador?->instituicao_id === $instituicaoTutoraId)
            );

            return [
                'id' => $item->id,
                'estado_anterior' => $item->estado_anterior,
                'estado_novo' => $item->estado_novo,
                'comentario' => $item->comentario,
                'tema' => $item->tema,
                'problema' => $item->problema,
                'objectivo_geral' => $item->objectivo_geral,
                'objectivos_especificos' => $item->objectivos_especificos,
                'created_at' => $item->created_at?->toIso8601String(),
                'utilizador' => [
                    'nome' => $mostrarNomeDoUtilizador
                        ? ($item->utilizador?->nome ?? $item->utilizador_nome ?? '—')
                        : ($ehTutora
                            ? (filled($nomeCurso)
                                ? PapHelper::rotuloGrupoDisciplinar($nomeCurso, $siglaInstituto)
                                : ($siglaInstituto ? "Grupo disciplinar do {$siglaInstituto}" : 'Grupo disciplinar'))
                            : ($item->utilizador?->nome ?? '—')),
                ],
            ];
        })->values();
    }

    /**
     * Obtém a sigla da instituição tutora noutro tenant.
     */
    public function siglaTutoraExterna(CursoTutelado $cursoTutelado): ?string
    {
        $tenantTutorId = $cursoTutelado->cursoTuteladoShared?->tenant_tutor_id;
        $tenant = $tenantTutorId ? Tenant::query()->find($tenantTutorId) : null;

        return $tenant?->run(
            fn (): ?string => Instituicao::query()->find($tenant->instituicao_id)?->sigla
        );
    }

    /**
     * Devolve o nome adequado para um decisor local ou externo.
     */
    private function rotuloDecisor(
        ?User $user,
        ?string $tenantExternoId,
        ?Instituicao $tutoraLocal,
        ?string $siglaTutora,
        ?string $nomeCurso,
    ): ?string {
        if ($tenantExternoId !== null && filled($nomeCurso)) {
            return PapHelper::rotuloGrupoDisciplinar($nomeCurso, $siglaTutora);
        }

        if ($user && $tutoraLocal && filled($nomeCurso)) {
            return PapHelper::nomeAprovador($user, $tutoraLocal, $nomeCurso);
        }

        return $user?->nome;
    }
}
