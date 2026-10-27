<?php

namespace App\Services\Tenant\Pauta\Index\Data;

use App\Models\Central\CursoTuteladoShared;
use Illuminate\Support\Collection;

/**
 * Transporta filtros normalizados e o âmbito autorizado da listagem de pautas.
 */
final readonly class PautaIndexContext
{
    /**
     * Guarda os filtros e identificadores que orientam as consultas local e remota.
     *
     * @param  array<string, mixed>  $filtros
     * @param  list<string>  $secretariadoCourseIds
     * @param  Collection<int, CursoTuteladoShared>  $secretariadoSharedLinks
     * @param  list<string>  $secretariadoRemoteCourseIds
     * @param  list<int|string>  $idsCursosCoordenados
     */
    public function __construct(
        public array $filtros,
        public string $instituicaoId,
        public string $instituicaoIdFiltro,
        public mixed $cursoTuteladoIdFiltro,
        public string $anoLectivoId,
        public bool $isProfessor,
        public bool $isCourseSecretary,
        public bool $isInstituteSecretary,
        public int|string|null $professorId,
        public array $secretariadoCourseIds,
        public Collection $secretariadoSharedLinks,
        public array $secretariadoRemoteCourseIds,
        public bool $isAllTuteladoInstitutionsFilter,
        public array $idsCursosCoordenados,
    ) {}
}
