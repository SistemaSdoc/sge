<?php

namespace App\Http\Resources\Tenant\CursoClasseTurno;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CursoClasseTurnoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'classe' => [
                'id' => $this->classe->id,
                'nome' => $this->classe->nome,
            ],
            'turnos' => $this->turnos->map(fn ($cct) => [
                'id' => $cct->id,
                'nome' => $cct->turno->nome,
            ]),
        ];
    }
}
