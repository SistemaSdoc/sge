<?php

namespace App\Services\Tenant\Pauta\Index\Data;

use Illuminate\Support\Collection;

/**
 * Agrupa instituições, cursos e turmas já preparados para a listagem.
 */
final readonly class PautaIndexData
{
    /**
     * Guarda as linhas preparadas por cada origem de dados.
     *
     * @param  Collection<int, array{id: string, nome: string}>  $instituicoes
     * @param  Collection<int, array{id: string, curso_key: string, nome: string, remote: bool}>  $cursos
     * @param  Collection<int, array{id: string, _created_at: int, nome: string, classe: string|null, turno: string|null, curso: string, curso_tutelado_id: string, curso_key: string, can: array{view_pauta: bool}}>  $turmas
     */
    public function __construct(
        public Collection $instituicoes,
        public Collection $cursos,
        public Collection $turmas,
    ) {}
}
