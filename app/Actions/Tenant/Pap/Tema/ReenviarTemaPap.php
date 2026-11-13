<?php

namespace App\Actions\Tenant\Pap\Tema;

use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\User;
use App\Services\Tenant\Pap\Aprovacao\ReenviarTemaPapPeloColegioService;
use App\Services\Tenant\Pap\Tema\TemaPapUnicidadeService;

class ReenviarTemaPap
{
    /**
     * Recebe os serviços de unicidade e de transição da aprovação.
     */
    public function __construct(
        private readonly TemaPapUnicidadeService $temaPapUnicidadeService,
        private readonly ReenviarTemaPapPeloColegioService $reenviarTemaPapPeloColegioService,
    ) {}

    /**
     * Valida o tema corrigido e delega a transição de reenvio à aprovação.
     *
     * @param  array{nome_grupo: string, tema_grupo: string, problema?: string|null, objectivo_geral?: string|null, objectivos_especificos?: string|null, estudo_caso?: string|null}  $dados
     */
    public function handle(GrupoPap $grupoPap, User $actor, array $dados): bool
    {
        $grupoPap->loadMissing('turma.cursoClasseTurno.cursoClasse');
        $turma = $grupoPap->turma;
        $dadosParaUnicidade = [
            ...$dados,
            'estudo_caso' => array_key_exists('estudo_caso', $dados)
                ? $dados['estudo_caso']
                : $grupoPap->estudo_caso,
        ];

        $this->temaPapUnicidadeService->validarUnicidade(
            $dadosParaUnicidade,
            (string) $turma?->cursoClasseTurno?->cursoClasse?->curso_tutelado_id,
            (string) $turma?->ano_lectivo_id,
            (string) $turma?->curso_classe_turno_id,
            (string) $grupoPap->getKey(),
        );

        return $this->reenviarTemaPapPeloColegioService->reenviar($grupoPap, $actor, $dados);
    }
}
