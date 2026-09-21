<?php

namespace App\Actions\Tenant\ElementoGrupoPap;

use App\Models\Tenant\GrupoPap;
use Illuminate\Support\Facades\DB;

class AddElementosGrupoPap
{
    /**
     * Adiciona os alunos validados ao grupo PAP.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(GrupoPap $grupoPap, array $validated): void
    {
        DB::transaction(function () use ($grupoPap, $validated): void {
            $grupoPap->elementos()->createMany(
                collect($validated['alunos'])
                    ->map(fn (string $alunoId): array => ['aluno_id' => $alunoId])
                    ->all()
            );
        });
    }
}
