<?php

namespace App\Actions\Tenant\Pap\Defesa;

use App\Models\Tenant\GrupoPap;
use Illuminate\Support\Carbon;

class DefinirDataDefesa
{
    /**
     * Regista a data, hora e local da defesa do grupo PAP.
     *
     * @param  array{data_defesa: string, hora_defesa: string, local_defesa: string}  $dados
     */
    public function handle(GrupoPap $grupoPap, array $dados): void
    {
        $grupoPap->update([
            'data_defesa' => Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $dados['data_defesa'].' '.$dados['hora_defesa'].':00'
            ),
            'local_defesa' => $dados['local_defesa'],
        ]);
    }
}
