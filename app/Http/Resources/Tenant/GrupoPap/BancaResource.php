<?php

namespace App\Http\Resources\Tenant\GrupoPap;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BancaResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'professor_id' => $this->professor->id,
            'nome' => $this->professor?->user->nome,
            'email' => $this->professor?->user->email,
            'funcao' => $this->funcao,
        ];
    }
}
