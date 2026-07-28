<?php

namespace App\Services;

use App\Models\GrupoPap;
use App\Models\HistoricoAprovacaoPap;
use Illuminate\Support\Collection;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AprovacaoTemaService
{
    /**
     * Buscar temas PAP pendentes dos cursos
     * tutelados onde o professor é coordenador.
     */
    public function temasPendentesParaCoordenador(string $professorId): Collection
    {
        $professor = \App\Models\Professor::find($professorId);
        $instituicaoId = $professor->user->instituicao_id ?? null;

        if (!$instituicaoId) {
            return collect();
        }

        // ✅ BUSCAR CURSOS TUTELADOS ONDE O PROFESSOR É COORDENADOR
        $cursosTutelados = \App\Models\CursoTutelado::query()
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
        ?string $comentario = null
    ): bool {
        return $this->alterarEstado(
            $grupoPap,
            $user,
            'aprovado',
            $comentario
        );
    }

    /**
     * Reprovar tema PAP
     */
    public function reprovar(
        GrupoPap $grupoPap,
        User $user,
        string $motivo
    ): bool {
        return $this->alterarEstado(
            $grupoPap,
            $user,
            'reprovado',
            $motivo
        );
    }

    /**
     * Solicitar melhoria no tema PAP
     */
    public function solicitarMelhoria(
        GrupoPap $grupoPap,
        User $user,
        string $recomendacao
    ): bool {
        return $this->alterarEstado(
            $grupoPap,
            $user,
            'melhoria-solicitada',
            $recomendacao
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
        ?string $comentario = null
    ): bool {

        // Só pode ser analisado se estiver pendente
        if (!$grupoPap->podeSerAprovado()) {
            return false;
        }

        return DB::transaction(function () use ($grupoPap, $user, $novoEstado, $comentario) {

            $estadoAnterior = $grupoPap->status_aprovacao;

            // Atualizar estado atual do grupo
            $grupoPap->update([
                'status_aprovacao' => $novoEstado,
                'aprovado_por_id' => $user->id,
                'data_aprovacao' => now(),
                'comentario_aprovacao' => $comentario,
            ]);

            // Registar histórico da decisão
            HistoricoAprovacaoPap::create([
                'grupo_pap_id' => $grupoPap->id,
                'utilizador_id' => $user->id,
                'estado_anterior' => $estadoAnterior,
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
        User $user
    ): bool {

        // Só pode reenviar se uma melhoria tiver sido solicitada
        if (!$grupoPap->podeSerReenviado()) {
            return false;
        }

        return DB::transaction(function () use ($grupoPap, $user) {

            $estadoAnterior = $grupoPap->status_aprovacao;

            // Voltar o tema para análise
            $grupoPap->update([
                'status_aprovacao' => 'pendente',
                'aprovado_por_id' => null,
                'data_aprovacao' => null,
            ]);

            // Registar o reenvio no histórico
            HistoricoAprovacaoPap::create([
                'grupo_pap_id' => $grupoPap->id,
                'utilizador_id' => $user->id,
                'estado_anterior' => $estadoAnterior,
                'estado_novo' => 'pendente',
                'comentario' => 'Tema corrigido e reenviado para nova análise.',
            ]);

            return true;
        });
    }
}