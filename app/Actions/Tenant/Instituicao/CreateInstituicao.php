<?php

namespace App\Actions\Tenant\Instituicao;

use App\Models\Tenant\Instituicao;
use Illuminate\Support\Facades\Storage;

class CreateInstituicao
{
    /**
     * Cria uma instituição e guarda o seu logo, quando fornecido.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(array $validated): Instituicao
    {
        $logo = null;

        try {
            if (isset($validated['logo'])) {
                $logo = $validated['logo']->store('logos', config('filesystems.default'));
                $validated['logo'] = $logo;
            }

            return Instituicao::create($validated);
        } catch (\Throwable $exception) {
            if ($logo) {
                Storage::disk(config('filesystems.default'))->delete($logo);
            }

            throw $exception;
        }
    }
}
