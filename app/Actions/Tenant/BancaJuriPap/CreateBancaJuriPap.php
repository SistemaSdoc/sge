<?php

namespace App\Actions\Tenant\BancaJuriPap;

use App\Models\Tenant\BancaJuriPap;
use App\Models\Tenant\GrupoPap;
use App\Notifications\Pap\JuradoAdicionadoBancaNotification;
use Illuminate\Support\Facades\DB;

class CreateBancaJuriPap
{
    /**
     * Cria um jurado na banca e envia a notificação de convocação.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(GrupoPap $grupoPap, array $validated): BancaJuriPap
    {
        return DB::transaction(function () use ($grupoPap, $validated): BancaJuriPap {
            $banca = $grupoPap->jurados()->create([
                'professor_id' => $validated['professor_id'],
                'funcao' => $validated['funcao'],
            ]);

            $grupoPap->load('turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao');
            $banca->load('professor.user');

            if ($banca->professor?->user) {
                $banca->professor->user->notify(
                    new JuradoAdicionadoBancaNotification($grupoPap, $banca)
                );
            }

            return $banca;
        });
    }
}
