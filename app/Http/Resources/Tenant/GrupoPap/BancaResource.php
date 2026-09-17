<?php

namespace App\Http\Resources\Tenant\GrupoPap;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BancaResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $user = $request->user('tenant');

        return [
            'id' => $this->id,
            'professor_id' => $this->professor->id,
            'user_id' => $this->professor->user_id,
            'is_current_user' => (string) $request->user('tenant')?->getKey() === (string) $this->professor->user_id,
            'can_view' => $user?->is($this->professor->user)
                || $user?->can('view', $this->professor),
            'nome' => $this->professor?->user->nome,
            'email' => $this->professor?->user->email,
            'funcao' => $this->funcao,
        ];
    }
}
