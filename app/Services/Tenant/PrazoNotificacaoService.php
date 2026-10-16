<?php

namespace App\Services\Tenant;

use App\Models\Tenant\JustificativaNaoSubmissao;
use App\Models\Tenant\PrazoProva;
use App\Models\Tenant\Professor;
use App\Models\Tenant\SubmissaoProva;
use App\Models\Tenant\User;
use App\Notifications\Diretor\JustificativaEnviadaNotificacao;
use App\Notifications\Diretor\NovaSubmissaoNotificacao;
use App\Notifications\Diretor\PrazoExpiradoDiretorNotificacao;
use App\Notifications\Professor\PrazoProvaNotificacao;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrazoNotificacaoService
{
    // ============================================================
    // NOVA SUBMISSÃO
    // ============================================================

    public function notificarDiretoresNovaSubmissao(SubmissaoProva $submissao): int
    {
        $prazo = $submissao->prazo;
        if (! $prazo) {
            Log::warning('Nova submissão sem prazo associado', ['submissao_id' => $submissao->id]);

            return 0;
        }

        $diretores = $this->buscarDiretores($prazo->instituicao_id);

        if ($diretores->isEmpty()) {
            Log::warning('Nenhum diretor para notificar sobre nova submissão', [
                'submissao_id'   => $submissao->id,
                'instituicao_id' => $prazo->instituicao_id,
            ]);

            return 0;
        }

        $enviadas = 0;

        foreach ($diretores as $diretor) {
            try {
                $diretor->notify(new NovaSubmissaoNotificacao($submissao));
                $enviadas++;
            } catch (\Throwable $e) {
                Log::error('Erro ao notificar diretor sobre nova submissão', [
                    'diretor_id'    => $diretor->id,
                    'submissao_id'  => $submissao->id,
                    'error'         => $e->getMessage(),
                ]);
            }
        }

        Log::info('Diretores notificados sobre nova submissão', [
            'submissao_id'   => $submissao->id,
            'instituicao_id' => $prazo->instituicao_id,
            'quantidade'     => $enviadas,
        ]);

        return $enviadas;
    }

    // ============================================================
    // NOTIFICAR PROFESSORES (criado, prorrogado, fechado, a_expirar, expirado)
    // ============================================================

    public function notificarProfessores(PrazoProva $prazo, string $tipo): int
    {
        $professores = $this->professoresElegiveis($prazo);

        if ($professores->isEmpty()) {
            Log::info('Nenhum professor elegível para notificar', [
                'prazo_id' => $prazo->id,
                'tipo'     => $tipo,
            ]);

            return 0;
        }

        $enviadas = 0;

        foreach ($professores as $professor) {
            if (! $professor->user) {
                continue;
            }

            try {
                $professor->user->notify(new PrazoProvaNotificacao($prazo, $tipo));
                $enviadas++;
            } catch (\Throwable $e) {
                Log::error('Erro ao notificar professor', [
                    'prazo_id'     => $prazo->id,
                    'professor_id' => $professor->id,
                    'user_id'      => $professor->user->id,
                    'error'        => $e->getMessage(),
                ]);
            }
        }

        Log::info('Notificações enviadas', [
            'prazo_id'   => $prazo->id,
            'tipo'       => $tipo,
            'quantidade' => $enviadas,
            'total'      => $professores->count(),
        ]);

        return $enviadas;
    }

    // ============================================================
    // PRAZO EXPIRADO → DIRETORES
    // ============================================================

    public function notificarDiretoresPrazoExpirado(PrazoProva $prazo): int
    {
        if (! $prazo->instituicao_id) {
            return 0;
        }

        $diretores = $this->buscarDiretores($prazo->instituicao_id);

        if ($diretores->isEmpty()) {
            Log::warning('Nenhum diretor para notificar sobre prazo expirado', [
                'prazo_id' => $prazo->id,
            ]);

            return 0;
        }

        //  Calcular estatísticas (com as chaves corretas)
        $stats = $this->calcularEstatisticas($prazo);

        //  Construir atribuições uma vez (fora do loop)
        $atribuicoes = $this->construirAtribuicoes($prazo);

        $enviadas = 0;

        foreach ($diretores as $diretor) {
            try {
                $diretor->notify(new PrazoExpiradoDiretorNotificacao(
                    $prazo,
                    $stats['total'],            // totalProfessores
                    $stats['submeteram'],       // submeteram
                    $stats['nao_submeteram'],   // naoSubmeteram
                    $stats['justificaram'],     // justificaram
                    $atribuicoes
                ));
                $enviadas++;
            } catch (\Throwable $e) {
                Log::error('Erro ao notificar diretor sobre prazo expirado', [
                    'diretor_id' => $diretor->id,
                    'prazo_id'   => $prazo->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        Log::info('Diretores notificados sobre prazo expirado', [
            'prazo_id'   => $prazo->id,
            'quantidade' => $enviadas,
            'stats'      => $stats,
        ]);

        return $enviadas;
    }

    // ============================================================
    // JUSTIFICATIVA ENVIADA → DIRETORES
    // ============================================================

    public function notificarDiretoresJustificativa(JustificativaNaoSubmissao $justificativa): int
    {
        $justificativa->loadMissing('prazo');
        $instituicaoId = $justificativa->prazo?->instituicao_id;

        if (! $instituicaoId) {
            return 0;
        }

        $diretores = $this->buscarDiretores($instituicaoId);

        if ($diretores->isEmpty()) {
            return 0;
        }

        $enviadas = 0;

        foreach ($diretores as $diretor) {
            try {
                $diretor->notify(new JustificativaEnviadaNotificacao($justificativa));
                $enviadas++;
            } catch (\Throwable $e) {
                Log::error('Erro ao notificar diretor sobre justificativa', [
                    'diretor_id'       => $diretor->id,
                    'justificativa_id' => $justificativa->id,
                    'error'            => $e->getMessage(),
                ]);
            }
        }

        Log::info('Diretores notificados sobre justificativa', [
            'justificativa_id' => $justificativa->id,
            'quantidade'       => $enviadas,
        ]);

        return $enviadas;
    }

    // ============================================================
    // MÉTODOS PRIVADOS
    // ============================================================

    /**
     * Lista utilizadores com role de diretor.
     */
    private function buscarDiretores(?string $instituicaoId = null): Collection
    {
        $query = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['Director', 'Subdirector', 'SuperAdmin']);
        });

        if ($instituicaoId) {
            $query->where('instituicao_id', $instituicaoId);
        }

        return $query->get();
    }

    /**
     * Lista professores elegíveis para um prazo.
     */
    private function professoresElegiveis(PrazoProva $prazo): Collection
    {
        // Prazo geral (sem disciplina)
        if (is_null($prazo->disciplina_id)) {
            $query = Professor::with('user')
                ->whereHas('user', fn ($q) => $q->where('instituicao_id', $prazo->instituicao_id));

            if ($prazo->classe_id) {
                $query->whereExists(function ($q) use ($prazo): void {
                    $q->select(DB::raw(1))
                        ->from('turma_disciplina_professor')
                        ->join('classe_turno_disciplina', 'turma_disciplina_professor.classe_turno_disciplina_id', '=', 'classe_turno_disciplina.id')
                        ->join('curso_classe_turno', 'classe_turno_disciplina.curso_classe_turno_id', '=', 'curso_classe_turno.id')
                        ->join('curso_classe', 'curso_classe_turno.curso_classe_id', '=', 'curso_classe.id')
                        ->whereColumn('turma_disciplina_professor.professor_id', 'professores.id')
                        ->where('curso_classe.classe_id', $prazo->classe_id);
                });
            }

            return $query->get();
        }

        // Prazo com disciplina
        return Professor::with('user')
            ->whereExists(function ($q) use ($prazo) {
                $q->select(DB::raw(1))
                    ->from('turma_disciplina_professor')
                    ->join('classe_turno_disciplina', 'turma_disciplina_professor.classe_turno_disciplina_id', '=', 'classe_turno_disciplina.id')
                    ->join('curso_classe_turno', 'classe_turno_disciplina.curso_classe_turno_id', '=', 'curso_classe_turno.id')
                    ->join('curso_classe', 'curso_classe_turno.curso_classe_id', '=', 'curso_classe.id')
                    ->whereColumn('turma_disciplina_professor.professor_id', 'professores.id')
                    ->where('classe_turno_disciplina.disciplina_id', $prazo->disciplina_id)
                    ->when($prazo->classe_id, fn ($q) => $q->where('curso_classe.classe_id', $prazo->classe_id));
            })
            ->whereHas('user', fn ($q) => $q->where('instituicao_id', $prazo->instituicao_id))
            ->get();
    }

    /**
     * Calcula estatísticas de cumprimento.
     * Agora com atribuições (professor + turma) para contagem correta.
     */
    private function calcularEstatisticas(PrazoProva $prazo): array
    {
        $atribuicoes = $this->construirAtribuicoes($prazo);
        $total = count($atribuicoes);

        $submeteram = collect($atribuicoes)->where('submeteu', true)->count();

        $justificaram = collect($atribuicoes)
            ->whereIn('justificativa_status', ['pendente', 'aceita'])
            ->count();

        $naoSubmeteram = $total - $submeteram;

        return [
            'total'          => $total,
            'submeteram'     => $submeteram,
            'nao_submeteram' => $naoSubmeteram,
            'justificaram'   => $justificaram,
        ];
    }

    /**
     * Constrói a lista de atribuições (professor + turma + estado).
     */
    private function construirAtribuicoes(PrazoProva $prazo): array
    {
        $atribuicoes = DB::table('turma_disciplina_professor as tdp')
            ->join('classe_turno_disciplina as ctd', 'tdp.classe_turno_disciplina_id', '=', 'ctd.id')
            ->join('curso_classe_turno as cct', 'ctd.curso_classe_turno_id', '=', 'cct.id')
            ->join('curso_classe as cc', 'cct.curso_classe_id', '=', 'cc.id')
            ->join('turmas as t', 't.curso_classe_turno_id', '=', 'cct.id')
            ->join('professores as p', 'p.id', '=', 'tdp.professor_id')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->where(function ($q) use ($prazo) {
                if ($prazo->disciplina_id) {
                    $q->where('ctd.disciplina_id', $prazo->disciplina_id);
                }
                if ($prazo->classe_id) {
                    $q->where('cc.classe_id', $prazo->classe_id);
                }
            })
            ->where('u.instituicao_id', $prazo->instituicao_id)
            ->select(
                'p.id as professor_id',
                'u.nome as professor_nome',
                't.id as turma_id',
                't.nome as turma_nome'
            )
            ->distinct()
            ->orderBy('u.nome')
            ->orderBy('t.nome')
            ->get();

        // Submissões: última versão por (professor + turma)
        $submissoes = SubmissaoProva::where('prazo_prova_id', $prazo->id)
            ->where('estado', '!=', 'substituido')
            ->get()
            ->groupBy(fn ($s) => $s->professor_id . '|' . $s->turma_id)
            ->map(fn ($g) => $g->sortByDesc('versao')->first());

        // Justificativas: por (professor + turma)
        $justificativas = JustificativaNaoSubmissao::where('prazo_prova_id', $prazo->id)
            ->get()
            ->groupBy(fn ($j) => $j->professor_id . '|' . $j->turma_id)
            ->map(fn ($g) => $g->first());

        return $atribuicoes->map(function ($atr) use ($submissoes, $justificativas) {
            $key = $atr->professor_id . '|' . $atr->turma_id;

            return [
                'professor_nome'       => $atr->professor_nome,
                'turma_nome'           => $atr->turma_nome,
                'submeteu'             => $submissoes->has($key),
                'justificativa_status' => $justificativas->get($key)?->status,
            ];
        })->toArray();
    }
}