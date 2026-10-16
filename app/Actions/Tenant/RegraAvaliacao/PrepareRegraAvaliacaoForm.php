<?php

namespace App\Actions\Tenant\RegraAvaliacao;

use App\Models\Tenant\Classe;
use App\Models\Tenant\NivelEnsino;
use App\Models\Tenant\RegraAvaliacao;

class PrepareRegraAvaliacaoForm
{
    /**
     * Prepara os níveis de ensino e as classes disponíveis nos formulários.
     *
     * @return array<string, mixed>
     */
    public function handle(?RegraAvaliacao $regraAvaliacao = null): array
    {
        $niveisEnsino = NivelEnsino::where('activo', 1)
            ->orderBy('ordem')
            ->get(['id', 'nome']);

        $classesPorNivel = Classe::select('classes.id', 'classes.nome', 'classes.ordem', 'curso_classe.nivel_ensino_id')
            ->join('curso_classe', 'classes.id', '=', 'curso_classe.classe_id')
            ->whereNotNull('curso_classe.nivel_ensino_id')
            ->distinct()
            ->orderBy('classes.ordem')
            ->get()
            ->groupBy('nivel_ensino_id')
            ->map(fn ($classes) => $classes->map->only(['id', 'nome'])->values());

        $formData = [
            'niveisEnsino' => $niveisEnsino,
            'classesPorNivel' => $classesPorNivel,
        ];

        if ($regraAvaliacao) {
            $formData['regraAvaliacao'] = $regraAvaliacao->load(['classe', 'nivelEnsino']);
        }

        return $formData;
    }
}
