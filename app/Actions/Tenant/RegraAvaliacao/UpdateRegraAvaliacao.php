<?php

namespace App\Actions\Tenant\RegraAvaliacao;

use App\Models\Tenant\RegraAvaliacao;

class UpdateRegraAvaliacao
{
    /**
     * Actualiza uma regra de avaliação existente.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(RegraAvaliacao $regraAvaliacao, array $validated): void
    {
        $regraAvaliacao->update($validated);
    }
}
