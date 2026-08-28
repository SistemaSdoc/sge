<?php

namespace App\Actions\Tenant\GrupoPap;

use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Turma;
use Illuminate\Support\Facades\DB;

/**
 * Cria um grupo PAP e os seus elementos.
 */
class CreateGrupoPap
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(Turma $turma, array $validated): GrupoPap
    {
        return DB::transaction(function () use ($turma, $validated): GrupoPap {
            $grupoPap = GrupoPap::create([
                'turma_id' => $turma->getKey(),
                'professor_tutor_id' => $validated['professor_tutor_id'],
                'nome_grupo' => $validated['nome_grupo'],
                'status_aprovacao' => GrupoPap::APROVACAO_RASCUNHO,
                'tema_grupo' => $validated['tema_grupo'] ?? null,
                'problema' => $validated['problema'] ?? null,
                'objectivos' => $validated['objectivos'] ?? null,
                'estudo_caso' => $validated['estudo_caso'] ?? null,
                'nota_final' => $validated['nota_final'] ?? null,
                'data_defesa' => $validated['data_defesa'] ?? null,
            ]);

            $grupoPap->elementos()->createMany(
                collect($validated['alunos'])->map(fn (string $alunoId): array => [
                    'aluno_id' => $alunoId,
                ])->all()
            );

            return $grupoPap;
        });
    }
}
