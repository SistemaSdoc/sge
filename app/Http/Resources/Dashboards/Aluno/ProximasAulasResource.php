<?php

namespace App\Http\Resources\Dashboards\Aluno;

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
        $professor = data_get($this, 'professor');
        $horario = data_get($this, 'horario');

        return [
            'id' => data_get($this, 'id'),
            'disciplina' => [
                'nome' => data_get($this, 'disciplina.nome'),
                'sigla' => data_get($this, 'disciplina.sigla'),
            ],
            'professor' => $professor ? [
                'nome' => data_get($professor, 'nome'),
            ] : null,
            'horario' => $horario ? [
                'hora_inicio' => data_get($horario, 'hora_inicio'),
                'hora_fim' => data_get($horario, 'hora_fim'),
            ] : null,
            'dia_label' => data_get($this, 'dia_label'),
            // 'dia_nome' => data_get($this, 'dia_nome'),
            'dia' => data_get($this, 'dia'),
        ];
    }
}
