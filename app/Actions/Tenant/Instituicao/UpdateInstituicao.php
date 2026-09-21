<?php

namespace App\Actions\Tenant\Instituicao;

use App\Models\Tenant\Instituicao;
use Illuminate\Support\Facades\Storage;

class UpdateInstituicao
{
    /**
     * Actualiza uma instituição e substitui o logo quando fornecido.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(Instituicao $instituicao, array $validated): void
    {
        $logoAnterior = $instituicao->logo;
        $logoNovo = null;

        try {
            if (isset($validated['logo'])) {
                $logoNovo = $validated['logo']->store('logos', config('filesystems.default'));
                $validated['logo'] = $logoNovo;
            }

            $instituicao->update($validated);

            if ($logoAnterior && $logoNovo) {
                Storage::disk(config('filesystems.default'))->delete($logoAnterior);
            }
        } catch (\Throwable $exception) {
            if ($logoNovo) {
                Storage::disk(config('filesystems.default'))->delete($logoNovo);
            }

            throw $exception;
        }
    }
}
