<?php

namespace App\Actions\Tenant\Aluno;

use App\Models\Tenant\Aluno;
use App\Models\Tenant\Turma;
use App\Traits\NotificaAluno;
use Illuminate\Support\Facades\DB;

class UpdateAluno
{
    use NotificaAluno;

    /**
     * Actualiza os dados do aluno e, quando indicado, a sua turma.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(Aluno $aluno, array $validated): void
    {
        $candidato = $aluno->inscricao?->candidato;
        abort_if($candidato === null, 404);

        $user = $aluno->user ?? $candidato->user;
        $dadosCandidato = [
            'nome' => $validated['nome'],
            'bi' => $validated['bi'],
            'email' => $validated['email'],
        ];

        DB::transaction(function () use ($candidato, $user, $dadosCandidato): void {
            if ($user !== null) {
                $dadosCandidato['user_id'] = $user->getKey();
            }

            $candidato->update($dadosCandidato);
            $user?->update([
                'nome' => $dadosCandidato['nome'],
                'bi' => $dadosCandidato['bi'],
                'email' => $dadosCandidato['email'],
            ]);
        });

        if ($validated['turma_id'] ?? null) {
            $turmaAtual = $aluno->turmas()->wherePivot('activo', true)->first();

            if (! $turmaAtual || $turmaAtual->id !== (int) $validated['turma_id']) {
                $turma = Turma::findOrFail($validated['turma_id']);

                if ($turmaAtual) {
                    $aluno->turmas()->updateExistingPivot($turmaAtual->id, [
                        'activo' => false,
                    ]);
                }

                $aluno->turmas()->syncWithoutDetaching([
                    $validated['turma_id'] => [
                        'activo' => true,
                    ],
                ]);

                if ($aluno->inscricao && $aluno->inscricao->curso_classe_turno_id !== $turma->curso_classe_turno_id) {
                    $aluno->inscricao->update([
                        'curso_classe_turno_id' => $turma->curso_classe_turno_id,
                    ]);
                }

                $this->notificarAlunoTransferidoTurma($aluno, $turma);
            }
        }
    }
}
