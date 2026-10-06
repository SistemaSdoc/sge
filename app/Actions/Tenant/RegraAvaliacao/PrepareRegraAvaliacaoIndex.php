<?php

namespace App\Actions\Tenant\RegraAvaliacao;

use App\Models\Tenant\RegraAvaliacao;
use Illuminate\Pagination\LengthAwarePaginator;

class PrepareRegraAvaliacaoIndex
{
    /**
     * Obtém e prepara as regras de avaliação para a listagem.
     */
    public function handle(): LengthAwarePaginator
    {
        return RegraAvaliacao::with(['instituicao', 'anoLectivo', 'classe', 'nivelEnsino'])
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->through(fn (RegraAvaliacao $regra): array => [
                'id' => $regra->id,
                'nome' => $regra->nome,
                'nivelEnsino' => $regra->nivelEnsino?->nome ?? 'Todos os níveis',
                'aplicacao' => $this->getAplicacao($regra),
            ]);
    }

    /**
     * Obtém o contexto de aplicação para uma regra.
     */
    private function getAplicacao(RegraAvaliacao $regra): string
    {
        if ($regra->classe_id && $regra->classe) {
            return $regra->classe->nome;
        }

        if ($regra->nivel_ensino_id && $regra->nivelEnsino) {
            return $regra->nivelEnsino->nome;
        }

        return 'Todas as classes';
    }
}
