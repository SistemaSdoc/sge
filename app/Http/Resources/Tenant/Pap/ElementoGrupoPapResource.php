<?php

namespace App\Http\Resources\Tenant\Pap;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ElementoGrupoPapResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Apresenta os dados de um aluno integrante do grupo PAP.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user('tenant');

        return [
            'id' => $this->id,
            'aluno_id' => $this->aluno->id,
            'user_id' => $this->aluno->user_id,
            'is_current_user' => (string) $user?->getKey() === (string) $this->aluno->user_id,
            'can_view' => $user?->is($this->aluno->user)
                || $user?->can('view', $this->aluno),
            'nome' => $this->aluno?->inscricao?->candidato?->nome,
            'email' => $this->aluno?->inscricao?->candidato?->email,
            'avatar' => $this->aluno?->user?->avatar_url,
            'matricula' => $this->aluno?->matricula,
            'nota_individual' => $this->nota_individual,
        ];
    }
}
