<?php

namespace App\Http\Resources\Central\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantIndexResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'domains' => $this->domains,
            'status' => $this->status,
            'instituicao' => $this->instituicao?->only([
                'id',
                'nome',
                'sigla',
            ]),
        ];
    }
}
