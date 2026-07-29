<?php

namespace App\Http\Resources\GrupoPap;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome_grupo' => $this->nome_grupo,
            'tema_grupo' => $this->tema_grupo,
            'estudo_caso' => $this->estudo_caso,
            'status' => $this->status,
            'status_aprovacao' => $this->status_aprovacao,
            'nota_final' => $this->nota_final,
            'data_defesa' => $this->data_defesa?->toIso8601String(),
            'local_defesa' => $this->local_defesa,
            'professor' => $this->professor ? [
                'id' => $this->professor->id,
                'nome' => $this->professor->user->nome,
                'email' => $this->professor->user->email,
            ] : null,
            'aprovado_por' => $this->aprovadoPor ? [
                'id' => $this->aprovadoPor->id,
                'nome' => $this->aprovadoPor->nome ?? null,
            ] : null,
        ];
    }
}
