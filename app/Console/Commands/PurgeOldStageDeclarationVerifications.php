<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:purge-old-stage-declaration-verifications')]
#[Description('Remove verificações de declarações de estágio com mais de 30 dias')]
class PurgeOldStageDeclarationVerifications extends Command
{
    public function handle(): int
    {
        $removidas = DB::connection('central')
            ->table('verificacoes_declaracoes_estagio')
            ->where('verificado_em', '<', now()->subDays(30))
            ->delete();

        $this->info("Verificações antigas removidas: {$removidas}.");

        return self::SUCCESS;
    }
}
