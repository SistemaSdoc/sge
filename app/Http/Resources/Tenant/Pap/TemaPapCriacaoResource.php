<?php

namespace App\Http\Resources\Tenant\Pap;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemaPapCriacaoResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Apresenta as opções necessárias à proposta de tema PAP.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'professores' => collect($this->professores)->map(fn ($professor) => [
                'id' => $professor->id,
                'nome' => $professor->user?->nome,
            ])->values(),
            'sugestoes_temas' => collect($this->sugestoes_temas)->map(fn ($sugestao) => [
                'id' => data_get($sugestao, 'id'),
                'titulo' => data_get($sugestao, 'titulo'),
                'descricao' => data_get($sugestao, 'descricao'),
            ])->values(),
        ];
    }
}
