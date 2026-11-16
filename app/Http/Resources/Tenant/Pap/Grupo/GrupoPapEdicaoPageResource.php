<?php

namespace App\Http\Resources\Tenant\Pap\Grupo;

use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GrupoPapEdicaoPageResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Prepara todos os dados do formulário de edição de grupo PAP.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource['user'];

        /** @var GrupoPap $grupoPap */
        $grupoPap = $this->resource['grupoPap'];

        return [
            'instituicao' => $this->resource['instituicao']->only('id', 'nome'),
            'cursoTutelado' => $this->resource['cursoTutelado']->only('id'),
            'cursoClasse' => $this->resource['cursoClasse']->only('id'),
            'cursoClasseTurno' => $this->resource['cursoClasseTurno']->only('id'),
            'turma' => $this->resource['turma']->only('id', 'nome'),
            'anoLectivoId' => $this->resource['turma']->ano_lectivo_id,
            'anosLectivos' => $this->resource['anosLectivos'],
            'form' => (new GrupoPapEdicaoResource((object) $this->resource['form']))->resolve($request),
            'podeAlterarTutor' => $grupoPap->professor_tutor_id !== null
                && $user->can('alterarTutor', $grupoPap),
        ];
    }
}
