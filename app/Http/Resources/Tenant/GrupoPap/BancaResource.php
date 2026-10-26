<?php

namespace App\Http\Resources\Tenant\GrupoPap;

use App\Models\Central\Tenant;
use App\Models\Tenant\Professor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BancaResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $user = $request->user('tenant');
        $professor = $this->professor;
        $professorExterno = null;

        if ($professor === null && $this->professor_externo_id && $this->professor_externo_tenant_id) {
            $tenant = Tenant::find($this->professor_externo_tenant_id);
            $professorExterno = $tenant?->run(fn (): ?Professor => Professor::with('user:id,nome,email')
                ->find($this->professor_externo_id));
        }

        $professorId = $professor?->id ?? $this->professor_externo_id;
        $professorUser = $professor?->user;

        return [
            'id' => $this->id,
            'professor_id' => $professorId,
            'user_id' => $professorUser?->id,
            'is_current_user' => (string) $user?->getKey() === (string) $professorUser?->id,
            'can_view' => $professor !== null && ($user?->is($professorUser)
                || $user?->can('view', $professor)),
            'nome' => $professorUser?->nome ?? $professorExterno?->user?->nome,
            'email' => $professorUser?->email ?? $professorExterno?->user?->email,
            'funcao' => $this->funcao,
        ];
    }
}
