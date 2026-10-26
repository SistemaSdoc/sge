<?php

namespace App\Actions\Tenant\RegraAvaliacao;

use App\Models\Central\AnoLectivo;
use App\Models\Tenant\RegraAvaliacao;

class CreateRegraAvaliacao
{
    /**
     * Cria uma regra de avaliação para a instituição e o ano lectivo activos.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(array $validated, string $instituicaoId): RegraAvaliacao
    {
        return RegraAvaliacao::create([
            ...$validated,
            'instituicao_id' => $instituicaoId,
            'ano_lectivo_id' => AnoLectivo::activo()?->id,
        ]);
    }
}
