<?php

namespace App\Actions\Tenant\Instituicao;

use App\Models\Tenant\Instituicao;
use Illuminate\Support\Facades\Storage;

class DeleteInstituicao
{
    /**
     * Remove uma instituição e o seu logo armazenado.
     */
    public function handle(Instituicao $instituicao): void
    {
        $logo = $instituicao->logo;
        $instituicao->delete();

        if ($logo) {
            Storage::disk(config('filesystems.default'))->delete($logo);
        }
    }
}
