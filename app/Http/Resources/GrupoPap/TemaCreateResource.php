<?php

namespace App\Http\Resources\GrupoPap;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemaCreateResource extends JsonResource
{
    public static $wrap = null;
    public function toArray(Request $request): array
    {
        return [
            'professores' => collect($this->professores)->map(fn($professor) => [
                'id' => $professor->id,
                'nome' => $professor->user?->nome,
            ])->values(),
        ];
    }
}