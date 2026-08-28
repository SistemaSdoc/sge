<?php

namespace App\Services\Tenant;

use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\HistoricoAprovacaoPap;
use App\Models\Tenant\Professor;
use App\Models\Tenant\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AprovacaoTemaService
{
    /**
     * Buscar temas PAP pendentes dos cursos
     * tutelados onde o professor é coordenador.
     */
    public function temasPendentesParaCoordenador(string $professorId): Collection
    {
        $professor = Professor::find($professorId);
        $instituicaoId = $professor->user->instituicao_id ?? null;

        if (! $instituicaoId) {
            return collect();
        }

        // ✅ BUSCAR CURSOS TUTELADOS ONDE O PROFESSOR É COORDENADOR
        $cursosTutelados = CursoTutelado::query()
            ->where('instituicao_tutora_id', $instituicaoId)
            ->whereHas(
                'professores',
                function ($query) use ($professorId) {
                    $query->where('professor_id', $professorId)
                        ->where('coordenador', 1);  // ← Verificar se é coordenador
                }
            )
            ->pluck('id');

        if ($cursosTutelados->isEmpty()) {
            return collect();
        }

        // Buscar grupos PAP pendentes desses cursos
        return GrupoPap::query()
            ->where('status_aprovacao', 'pendente')
            ->whereHas(
                'turma.cursoClasseTurno.cursoClasse',
                function ($query) use ($cursosTutelados) {
                    $query->whereIn(
                        'curso_tutelado_id',
                        $cursosTutelados
                    );
                }
            )
            ->with([
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
                'professor.user',
                'elementos.aluno',
            ])
            ->latest()
            ->get();
    }

    /**
     * Aprovar tema PAP
     */
    public function aprovar(
        GrupoPap $grupoPap,
        User $user,
        ?string $comentario = null,
        ?string $actorTenantId = null,
    ): bool {
        return $this->alterarEstado(
            $grupoPap,
            $user,
            'aprovado',
            $comentario,
            $actorTenantId,
        );
    }

    /**
     * Reprovar tema PAP
     */
    public function reprovar(
        GrupoPap $grupoPap,
        User $user,
        string $motivo,
        ?string $actorTenantId = null,
    ): bool {
        return $this->alterarEstado(
            $grupoPap,
            $user,
            'reprovado',
            $motivo,
            $actorTenantId,
        );
    }

    /**
     * Solicitar melhoria no tema PAP
     */
    public function solicitarMelhoria(
        GrupoPap $grupoPap,
        User $user,
        string $recomendacao,
        ?string $actorTenantId = null,
    ): bool {
        return $this->alterarEstado(
            $grupoPap,
            $user,
            'melhoria-solicitada',
            $recomendacao,
            $actorTenantId,
        );
    }

    /**
     * Alterar estado do tema PAP
     * e criar registo no histórico.
     */
    private function alterarEstado(
        GrupoPap $grupoPap,
        User $user,
        string $novoEstado,
        ?string $comentario = null,
        ?string $actorTenantId = null,
    ): bool {

        $grupoPap->assertTutelaActiva();

        // Só pode ser analisado se estiver pendente
        if (! $grupoPap->podeSerAprovado()) {
            return false;
        }

        return DB::transaction(function () use ($grupoPap, $user, $novoEstado, $comentario, $actorTenantId) {

            $estadoAnterior = $grupoPap->status_aprovacao;
            $isExternalActor = $actorTenantId !== null
                && $actorTenantId !== (string) tenancy()->tenant->getTenantKey();

            // Atualizar estado atual do grupo
            $grupoPap->update([
                'status_aprovacao' => $novoEstado,
                'aprovado_por_id' => $isExternalActor ? null : $user->id,
                'aprovado_por_externo_id' => $isExternalActor ? $user->id : null,
                'aprovado_por_externo_tenant_id' => $isExternalActor ? $actorTenantId : null,
                'aprovado_por_nome' => $isExternalActor ? $user->nome : null,
                'data_aprovacao' => now(),
                'comentario_aprovacao' => $comentario,
                ...($novoEstado === 'aprovado' ? ['status' => 'em-andamento'] : []),
            ]);

            // Registar histórico da decisão
            HistoricoAprovacaoPap::create([
                'grupo_pap_id' => $grupoPap->id,
                'utilizador_id' => $isExternalActor ? null : $user->id,
                'utilizador_externo_id' => $isExternalActor ? $user->id : null,
                'utilizador_externo_tenant_id' => $isExternalActor ? $actorTenantId : null,
                'utilizador_nome' => $isExternalActor ? $user->nome : null,
                'estado_anterior' => $estadoAnterior,
                'tema' => $grupoPap->tema_grupo,
                'problema' => $grupoPap->problema,
                'objectivos' => $grupoPap->objectivos,
                'estado_novo' => $novoEstado,
                'comentario' => $comentario,
            ]);

            return true;
        });
    }

    /**
     * Reenviar tema PAP após solicitação de melhoria.
     *
     * O colégio corrige o tema e envia novamente
     * para análise da instituição tutora.
     */
    public function reenviar(
        GrupoPap $grupoPap,
        User $user,
        array $dados
    ): bool {

        $grupoPap->assertTutelaActiva();

        // Só pode reenviar se uma melhoria tiver sido solicitada
        if (! $grupoPap->podeSerReenviado()) {
            return false;
        }

        return DB::transaction(function () use ($grupoPap, $user, $dados) {

            $estadoAnterior = $grupoPap->status_aprovacao;

            // Voltar o tema para análise
            $grupoPap->update([
                'nome_grupo' => $dados['nome_grupo'],
                'tema_grupo' => $dados['tema_grupo'],
                'problema' => $dados['problema'] ?? null,
                'objectivos' => $dados['objectivos'] ?? null,
                'status_aprovacao' => GrupoPap::APROVACAO_SUBMETIDO,
                'aprovado_por_id' => null,
                'data_aprovacao' => null,
            ]);

            // Registar o reenvio no histórico
            HistoricoAprovacaoPap::create([
                'grupo_pap_id' => $grupoPap->id,
                'utilizador_id' => $user->id,
                'estado_anterior' => $estadoAnterior,
                'tema' => $grupoPap->tema_grupo,
                'problema' => $grupoPap->problema,
                'objectivos' => $grupoPap->objectivos,
                'estado_novo' => GrupoPap::APROVACAO_SUBMETIDO,
                'comentario' => 'Tema corrigido e reenviado para revisão do professor tutor.',
            ]);

            return true;
        });
    }
}
