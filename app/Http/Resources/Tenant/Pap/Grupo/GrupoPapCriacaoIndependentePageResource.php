<?php

namespace App\Http\Resources\Tenant\Pap\Grupo;

use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GrupoPapCriacaoIndependentePageResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Prepara todos os dados do formulário independente de grupo PAP.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource['user'];

        return [
            'instituicao' => $this->resource['instituicao']->only('id', 'nome'),
            'cursosTutelados' => $this->resource['cursosTutelados'],
            'classes' => $this->resource['classes'],
            'turnos' => $this->resource['turnos'],
            'turmas' => $this->resource['turmas'],
            'professores' => $this->resource['professores'],
            'alunos' => $this->resource['alunos'],
            'can' => [
                'create' => $user->can('create', GrupoPap::class),
            ],
        ];
    }
}
