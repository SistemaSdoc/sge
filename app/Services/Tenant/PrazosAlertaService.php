<?php

namespace App\Services\Tenant;

use App\Models\Tenant\JustificativaNaoSubmissao;
use App\Models\Tenant\PrazoProva;
use App\Models\Tenant\SubmissaoProva;
use App\Models\Tenant\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PrazosAlertaService
{
    /**
     * Lista os prazos que o professor ainda NÃO fechou, com o estado
     * de submissão por turma.
     *
     * Regras:
     *  - status do prazo = 'aberto'  (expirado ja não continua a ser mostrado
     *    enquanto não for fechado manualmente)
     *  - só prazos onde o professor tem turmas a submeter
     *  - desaparece quando:
     *      a) o prazo é 'fechado', OU
     *      b) o professor já submeteu TODAS as turmas
     *  - aparece como "já submetido" se submeteu mas o prazo continua aberto
     */
    public function paraProfessor(User $user): Collection
    {
        $professor = $user->professor;

        if (! $professor) {
            return collect();
        }

        $prazos = PrazoProva::query()
            ->where('instituicao_id', $user->instituicao_id)
            ->whereIn('status', ['aberto'])
            ->orderBy('data_limite')
            ->with(['disciplina', 'classe'])
            ->get();

        if ($prazos->isEmpty()) {
            return collect();
        }

        // Pré-carrega submissões e justificativas do professor
        $submissoes = SubmissaoProva::query()
            ->whereIn('prazo_prova_id', $prazos->pluck('id'))
            ->where('professor_id', $professor->id)
            ->where('estado', '!=', 'substituido')
            ->get()
            ->groupBy('prazo_prova_id')
            ->map(fn ($g) => $g->groupBy('turma_id')->map->first());

        $justificativas = JustificativaNaoSubmissao::query()
            ->whereIn('prazo_prova_id', $prazos->pluck('id'))
            ->where('professor_id', $professor->id)
            ->get()
            ->groupBy('prazo_prova_id');

        $resultado = collect();

        foreach ($prazos as $prazo) {
            $turmas = $this->turmasDoProfessorNoPrazo($prazo, $professor);

            if ($turmas->isEmpty()) {
                continue;
            }

            $subsDoPrazo = $submissoes->get($prazo->id, collect());

            // Detalhe por turma
            $turmasInfo = $turmas->map(function ($turma) use ($subsDoPrazo) {
                $sub = $subsDoPrazo->get($turma->id);

                return [
                    'turma_id' => $turma->id,
                    'turma_nome' => $turma->nome,
                    'submeteu' => $sub !== null,
                    'submissao_id' => $sub?->id,
                    'estado' => $sub?->estado,
                    'estado_label' => $sub?->estado_label,
                ];
            })->values();

            $totalTurmas = $turmasInfo->count();
            $totalSubmetidas = $turmasInfo->where('submeteu', true)->count();
            $tudoSubmetido = $totalTurmas > 0 && $totalSubmetidas === $totalTurmas;

            $justificativa = $justificativas->get($prazo->id)?->first();
            $diasRestantes = (int) now()->diffInDays($prazo->data_limite, false);

            $resultado->push([
                'prazo_id' => $prazo->id,
                'titulo' => $prazo->titulo ?? $prazo->tipo_prova,
                'disciplina' => $prazo->disciplina?->nome ?? 'Todas',
                'classe' => $prazo->classe?->nome ?? 'Todas',
                'periodo' => $prazo->periodo,
                'data_limite' => $prazo->data_limite->format('d/m/Y H:i'),
                'dias_restantes' => $diasRestantes,
                'urgente' => $diasRestantes <= 2,
                'expirado' => $diasRestantes < 0,
                'status' => $prazo->status,
                'total_turmas' => $totalTurmas,
                'total_submetidas' => $totalSubmetidas,
                'tudo_submetido' => $tudoSubmetido,   // dentro do array
                'turmas' => $turmasInfo,
                'justificativa' => $justificativa ? [
                    'status' => $justificativa->status,
                    'status_label' => $justificativa->status_label,
                ] : null,
                'url' => route('tenant.dashboard.professor.provas.submeter', $prazo->id),
            ]);
        }

        return $resultado->values();
    }

    private function turmasDoProfessorNoPrazo(PrazoProva $prazo, $professor): Collection
    {
        $query = DB::table('turma_disciplina_professor as tdp')
            ->join('classe_turno_disciplina as ctd', 'tdp.classe_turno_disciplina_id', '=', 'ctd.id')
            ->join('curso_classe_turno as cct', 'ctd.curso_classe_turno_id', '=', 'cct.id')
            ->join('turmas as t', 't.curso_classe_turno_id', '=', 'cct.id')
            ->join('curso_classe as cc', 'cct.curso_classe_id', '=', 'cc.id')
            ->where('tdp.professor_id', $professor->id);

        if ($prazo->disciplina_id) {
            $query->where('ctd.disciplina_id', $prazo->disciplina_id);
        }

        if ($prazo->classe_id) {
            $query->where('cc.classe_id', $prazo->classe_id);
        }

        return $query
            ->select('t.id', 't.nome')
            ->distinct()
            ->orderBy('t.nome')
            ->get()
            ->map(fn ($t) => (object) ['id' => $t->id, 'nome' => $t->nome]);
    }
}
