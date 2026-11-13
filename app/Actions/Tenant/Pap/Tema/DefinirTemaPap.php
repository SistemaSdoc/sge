<?php

namespace App\Actions\Tenant\Pap\Tema;

use App\Models\Tenant\GrupoPap;
use App\Notifications\Pap\TemaDefinidoNotification;
use App\Services\Tenant\Pap\Tema\TemaPapUnicidadeService;
use Illuminate\Support\Facades\Notification;

class DefinirTemaPap
{
    /**
     * Recebe o serviço que valida a unicidade da proposta.
     */
    public function __construct(private readonly TemaPapUnicidadeService $temaPapUnicidadeService) {}

    /**
     * Regista a proposta de tema e notifica o tutor e os elementos.
     *
     * @param  array{tema_grupo?: string|null, estudo_caso?: string|null, professor_tutor_id?: int|string|null, problema?: string|null, objectivo_geral?: string|null, objectivos_especificos?: string|null}  $dados
     */
    public function handle(GrupoPap $grupoPap, array $dados, string $campoTema = 'tema_grupo'): void
    {
        $grupoPap->loadMissing('turma.cursoClasseTurno.cursoClasse', 'alunos.user', 'professor.user');
        $turma = $grupoPap->turma;
        $this->temaPapUnicidadeService->validarUnicidade(
            $dados,
            (string) $turma?->cursoClasseTurno?->cursoClasse?->curso_tutelado_id,
            (string) $turma?->ano_lectivo_id,
            (string) $turma?->curso_classe_turno_id,
            (string) $grupoPap->getKey(),
            $campoTema,
        );

        $grupoPap->update([
            ...array_intersect_key($dados, array_flip([
                'tema_grupo',
                'professor_tutor_id',
                'problema',
                'objectivo_geral',
                'objectivos_especificos',
                'estudo_caso',
            ])),
            'status_aprovacao' => GrupoPap::APROVACAO_SUBMETIDO,
        ]);

        $destinatarios = $grupoPap->alunos->map->user->filter();
        $tutorUser = $grupoPap->professor?->user;

        if ($tutorUser) {
            $destinatarios = $destinatarios->push($tutorUser)->unique('id');
        }

        Notification::send($destinatarios, new TemaDefinidoNotification($grupoPap));
    }
}
