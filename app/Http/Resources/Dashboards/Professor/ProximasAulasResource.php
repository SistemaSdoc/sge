<?php

namespace App\Http\Resources\Dashboards\Professor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProximasAulasResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $horario = data_get($this, 'horario');

        return [
            'id' => data_get($this, 'id'),
            'disciplina' => [
                'nome' => data_get($this, 'disciplina.nome'),
            ],
            'turma' => [
                'id' => data_get($this, 'turma.id'),
                'nome' => data_get($this, 'turma.nome'),
            ],
            'horario' => $horario ? [
                'hora_inicio' => data_get($horario, 'hora_inicio'),
                'hora_fim' => data_get($horario, 'hora_fim'),
            ] : null,
            'dia_label' => data_get($this, 'dia_label'),
            'dia_nome' => data_get($this, 'dia_nome'),
            'dia' => data_get($this, 'dia'),
        ];
    }
}
