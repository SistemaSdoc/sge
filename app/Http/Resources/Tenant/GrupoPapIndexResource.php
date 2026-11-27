<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class GrupoPapIndexResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
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
