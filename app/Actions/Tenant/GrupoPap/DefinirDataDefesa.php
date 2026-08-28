<?php

namespace App\Actions\Tenant\GrupoPap;

use App\Models\Tenant\GrupoPap;
use Illuminate\Support\Carbon;

/**
 * Define a data, hora e local da defesa de um grupo PAP.
 */
class DefinirDataDefesa
{
    /**
     * @param  array{data_defesa: string, hora_defesa: string, local_defesa: string}  $validated
     */
    public function handle(GrupoPap $grupoPap, array $validated): void
    {
        $grupoPap->update([
            'data_defesa' => Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $validated['data_defesa'].' '.$validated['hora_defesa'].':00'
            ),
            'local_defesa' => $validated['local_defesa'],
        ]);
    }
}
