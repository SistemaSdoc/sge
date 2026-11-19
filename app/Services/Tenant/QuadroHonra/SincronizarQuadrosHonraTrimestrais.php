<?php

namespace App\Services\Tenant\QuadroHonra;

use App\Actions\Tenant\QuadroHonra\GerarQuadroHonra;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\QuadroHonra;
use App\Models\Tenant\User;

class SincronizarQuadrosHonraTrimestrais
{
    public function __construct(
        private readonly GerarQuadroHonra $gerarQuadroHonra,
    ) {}

    public function sincronizar(
        string $instituicaoId,
        string $anoLectivoId,
        int $periodo,
        ?User $utilizador = null,
    ): int {
        $instituicao = Instituicao::query()->findOrFail($instituicaoId);
        $dadosBase = [
            'ano_lectivo_id' => $anoLectivoId,
            'periodo' => $periodo,
            'media_minima' => (float) $instituicao->quadro_honra_media_minima,
            'limite_alunos' => (int) $instituicao->quadro_honra_limite_alunos,
        ];
        $quadro = $this->gerarQuadroHonra->handle($dadosBase, $instituicaoId, $utilizador, true);

        return $quadro instanceof QuadroHonra ? 1 : 0;
    }

    public function recalcularUltimoTrimestreGerado(
        string $instituicaoId,
        string $anoLectivoId,
        User $utilizador,
    ): int {
        $periodo = QuadroHonra::query()
            ->where('ano_lectivo_id', $anoLectivoId)
            ->where('tipo', 'geral')
            ->whereNotNull('periodo')
            ->max('periodo');

        if ($periodo === null) {
            return 0;
        }

        return $this->sincronizar(
            $instituicaoId,
            $anoLectivoId,
            (int) $periodo,
            $utilizador,
        );
    }
}
