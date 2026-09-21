<?php

namespace App\Actions\Tenant\ElementoGrupoPap;

use App\Models\Tenant\ElementoGrupoPap;
use App\Models\Tenant\GrupoPap;
use App\Notifications\Pap\NotaAtribuidaNotification;
use Illuminate\Support\Facades\DB;

class AssignNotaElementoGrupoPap
{
    /**
     * Atribui a nota individual e conclui o grupo quando todos os elementos têm nota.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(GrupoPap $grupoPap, ElementoGrupoPap $elementoGrupoPap, array $validated): void
    {
        DB::transaction(function () use ($grupoPap, $elementoGrupoPap, $validated): void {
            $elementoGrupoPap->update([
                'nota_individual' => $validated['nota_individual'],
            ]);

            if ($grupoPap->elementos()->whereNull('nota_individual')->doesntExist()) {
                $grupoPap->update(['status' => 'concluido']);
            }

            $grupoPap->loadMissing(
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao',
                'alunos.user',
            );
            $elementoGrupoPap->loadMissing('aluno.user');

            $aluno = $elementoGrupoPap->aluno?->user;

            if ($aluno) {
                $aluno->notify(new NotaAtribuidaNotification($grupoPap, $elementoGrupoPap));
            }
        });
    }
}
