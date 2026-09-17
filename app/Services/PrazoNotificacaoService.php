<?php

namespace App\Services;

use App\Models\PrazoProva;
use App\Models\Professor;
use App\Notifications\PrazoProvaNotificacao;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;  
use App\Models\SubmissaoProva;
use App\Models\JustificativaNaoSubmissao; 
use App\Notifications\PrazoExpiradoDiretorNotificacao;
use App\Notifications\JustificativaEnviadaNotificacao; 
use App\Notifications\NovaSubmissaoNotificacao;

class PrazoNotificacaoService
{
    public function notificarDiretoresNovaSubmissao(SubmissaoProva $submissao): int
{
    // 🔥 Buscar o prazo com instituicao
    $prazo = $submissao->prazo;
    if (!$prazo) {
        Log::warning('Nova submissão sem prazo associado', ['submissao_id' => $submissao->id]);
        return 0;
    }

    // 🔥 Diretores apenas da mesma instituição do prazo
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
        } catch (\Exception $e) {
            Log::error('Erro ao notificar diretor sobre nova submissão', [
                'diretor_id'   => $diretor->id,
                'submissao_id' => $submissao->id,
                'error'        => $e->getMessage(),
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

    /**
     * Notifica todos os professores elegíveis para um prazo.
     *
     * @return int Número de notificações enviadas
     */
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
            if (!$professor->user) {
                continue;
            }

            try {
                // Sem queue – envio síncrono
                $professor->user->notify(new PrazoProvaNotificacao($prazo, $tipo));
                $enviadas++;
            } catch (\Exception $e) {
                Log::error('Erro ao notificar professor', [
                    'prazo_id'      => $prazo->id,
                    'professor_id'  => $professor->id,
                    'user_id'       => $professor->user->id,
                    'error'         => $e->getMessage(),
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

    /**
     * Lista professores elegíveis para um prazo.
     */
    private function professoresElegiveis(PrazoProva $prazo): Collection
    {
        // Prazo geral (sem disciplina) → todos os professores
        if (is_null($prazo->disciplina_id)) {
            return Professor::with('user')->get();
        }

        // Prazo com disciplina → professores que a lecionam
        return Professor::with('user')
            ->whereExists(function ($query) use ($prazo) {
                $query->select(DB::raw(1))
                    ->from('turma_disciplina_professor')
                    ->join(
                        'classe_turno_disciplina',
                        'turma_disciplina_professor.classe_turno_disciplina_id',
                        '=',
                        'classe_turno_disciplina.id'
                    )
                    ->whereColumn('turma_disciplina_professor.professor_id', 'professores.id')
                    ->where('classe_turno_disciplina.disciplina_id', $prazo->disciplina_id);
            })
            ->get();
    }
     /**
     * Notifica os diretores que um prazo expirou, com estatísticas.
     */
    public function notificarDiretoresPrazoExpirado(PrazoProva $prazo): int
    {
        $diretores = $this->buscarDiretores();

        if ($diretores->isEmpty()) {
            Log::warning('Nenhum diretor para notificar sobre prazo expirado', [
                'prazo_id' => $prazo->id,
            ]);
            return 0;
        }

        // Calcular estatísticas
        $stats = $this->calcularEstatisticas($prazo);

        $enviadas = 0;

        foreach ($diretores as $diretor) {
            try {
                $diretor->notify(new PrazoExpiradoDiretorNotificacao(
                    $prazo,
                    $stats['total'],
                    $stats['submeteram'],
                    $stats['nao_submeteram'],
                    $stats['justificaram']
                ));
                $enviadas++;
            } catch (\Exception $e) {
                Log::error('Erro ao notificar diretor', [
                    'diretor_id' => $diretor->id,
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

    /**
     * Notifica os diretores que um professor enviou uma justificativa.
     */
    public function notificarDiretoresJustificativa(JustificativaNaoSubmissao $justificativa): int
    {
        $diretores = $this->buscarDiretores();

        if ($diretores->isEmpty()) {
            return 0;
        }

        $enviadas = 0;

        foreach ($diretores as $diretor) {
            try {
                $diretor->notify(new JustificativaEnviadaNotificacao($justificativa));
                $enviadas++;
            } catch (\Exception $e) {
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
     * Calcula estatísticas de cumprimento de um prazo.
     */
    private function calcularEstatisticas(PrazoProva $prazo): array
    {
        $professores = $this->professoresElegiveis($prazo);
        $total = $professores->count();

        // Professores que submeteram (última versão não substituída)
        $submeteram = SubmissaoProva::where('prazo_prova_id', $prazo->id)
            ->where('estado', '!=', 'substituido')
            ->distinct('professor_id')
            ->count('professor_id');

        // Professores que justificaram
        $justificaram = JustificativaNaoSubmissao::where('prazo_prova_id', $prazo->id)
            ->distinct('professor_id')
            ->count('professor_id');

        $naoSubmeteram = $total - $submeteram;

        return [
            'total'          => $total,
            'submeteram'     => $submeteram,
            'nao_submeteram' => $nao_submeteram,
            'justificaram'   => $justificaram,
        ];
    }


}