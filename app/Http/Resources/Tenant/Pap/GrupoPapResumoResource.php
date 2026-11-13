<?php

namespace App\Http\Resources\Tenant\Pap;

use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class GrupoPapResumoResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Apresenta os dados essenciais do grupo numa lista resumida.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        return [
            'id' => $this->id,
            'nome' => $this->nome_grupo,
            'tema' => $this->tema_grupo,
            'status' => $this->status,
            'nota_final' => $this->nota_final,
            'can' => [
                'view' => $user?->can('view', $this->resource) ?? false,
                'update' => $user?->can('update', $this->resource) ?? false,
            ],
        ];
    }
}
