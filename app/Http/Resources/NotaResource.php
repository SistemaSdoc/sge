<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotaResource extends JsonResource
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
            'periodo' => $this->periodo,
            'mac' => $this->mac,                    // float|null (mutator get)
            'nota_prova_professor' => $this->nota_prova_professor,   // float|null (mutator get)
            'nota_prova_trimestral' => $this->nota_prova_trimestral,  // float|null (mutator get)
            'media_trimestral' => $this->media_trimestral,       // múltiplo 0.5 | null
            'media_final' => $this->media_final,            // múltiplo 0.5 | null
            'faltas' => $this->faltas,                 // int ≥ 0
            'situacao_trimestral' => $this->situacao_trimestral,
            'situacao_anual' => $this->situacao_anual,         // só preenchido no período 3
        ];
    }
}
