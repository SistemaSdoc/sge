<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->nome,
            'email' => $this->email,
            'instituicao_id' => $this->instituicao_id,
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
        ];
    }
}
