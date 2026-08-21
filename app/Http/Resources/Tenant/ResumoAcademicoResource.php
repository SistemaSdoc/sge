<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResumoAcademicoResource extends JsonResource
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
            'disciplina' => [
                'id' => $this->turmaDisciplinaProfessor->classeTurnoDisciplina->disciplina->id,
                'nome' => $this->turmaDisciplinaProfessor->classeTurnoDisciplina->disciplina->nome,
                'sigla' => $this->turmaDisciplinaProfessor->classeTurnoDisciplina->disciplina->sigla,
            ],
            'professor' => [
                'id' => $this->turmaDisciplinaProfessor->professor->id,
                'nome' => $this->turmaDisciplinaProfessor->professor->user->nome,
            ],
            'periodo' => $this->periodo,
            'faltas' => $this->faltas,
            'mac' => (float) $this->mac,
            'nota_prova_professor' => (float) $this->nota_prova_professor,
            'nota_prova_trimestral' => (float) $this->nota_prova_trimestral,
            'media_trimestral' => (float) $this->media_trimestral,
            'media_final' => (float) $this->media_final,
            'situacao_trimestral' => $this->situacao_trimestral,
            'situacao_anual' => $this->situacao_anual,
            'observacao' => $this->observacao,
        ];
    }
}
