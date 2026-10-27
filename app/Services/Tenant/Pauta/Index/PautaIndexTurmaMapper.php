<?php

namespace App\Services\Tenant\Pauta\Index;

use App\Models\Tenant\Turma;
use App\Models\Tenant\User;

/**
 * Converte modelos de turma para as linhas apresentadas na listagem de pautas.
 */
final class PautaIndexTurmaMapper
{
    /**
     * Define as relações necessárias para formatar uma turma.
     *
     * @return list<string>
     */
    public function relacoesNecessarias(): array
    {
        return [
            'cursoClasseTurno.cursoClasse.classe:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'cursoClasseTurno.turno:id,nome',
            'anoLectivo:id,nome',
        ];
    }

    /**
     * Formata uma turma e calcula a capacidade de consulta da respectiva pauta.
     *
     * @return array{id: string, _created_at: int, nome: string, classe: string|null, turno: string|null, curso: string, curso_tutelado_id: string, curso_key: string, can: array{view_pauta: bool}}
     */
    public function mapear(Turma $turma, User $user, bool $tutorRemoto = false): array
    {
        $cursoTutelado = $turma->cursoClasseTurno?->cursoClasse?->cursoTutelado;

        // A data é mantida apenas até à ordenação final e removida pelo presenter.
        return [
            'id' => (string) $turma->getKey(),
            '_created_at' => $turma->created_at?->getTimestamp() ?? 0,
            'nome' => $turma->nome,
            'classe' => $turma->cursoClasseTurno?->cursoClasse?->classe?->nome,
            'turno' => $turma->cursoClasseTurno?->turno?->nome,
            'curso' => $cursoTutelado?->instituicaoCurso?->curso?->nome ?? 'Curso sem nome',
            'curso_tutelado_id' => (string) $cursoTutelado?->getKey(),
            'curso_key' => (string) ($cursoTutelado?->instituicaoCurso?->curso_id ?? $cursoTutelado?->getKey()),
            'can' => [
                'view_pauta' => $tutorRemoto || $user->can('pauta.view', $turma),
            ],
        ];
    }
}
