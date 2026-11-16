<?php

namespace App\Http\Resources\Tenant\Pap\Grupo;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GrupoPapCriacaoPageResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Prepara todos os dados do formulário de criação de grupo PAP.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $instituicao = $this->resource['instituicao'];
        $cursoTutelado = $this->resource['cursoTutelado'];
        $cursoClasse = $this->resource['cursoClasse'];
        $cursoClasseTurno = $this->resource['cursoClasseTurno'];
        $turma = $this->resource['turma'];

        return [
            'instituicao' => $instituicao->only('id'),
            'cursoTutelado' => $cursoTutelado->only('id', 'nome'),
            'cursoClasse' => $cursoClasse->only('id', 'nome'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id'),
            'anoLectivoId' => $turma->ano_lectivo_id,
            'anosLectivos' => $this->resource['anosLectivos'],
            'form' => (new GrupoPapCriacaoResource((object) $this->resource['options']))->resolve($request),
        ];
    }
}
