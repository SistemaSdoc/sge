<?php

namespace App\Http\Resources\Aluno;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlunoResourceShow extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $turmaActual = $this->turmaActual()->first();

        return [
            'id' => $this->id,
            'nome' => $this->user->nome,
            'email' => $this->user->email,
            'bi' => $this->user->bi,
            'telefone' => $this->user->telefone,
            'matricula' => $this->matricula,
            'turma_actual' => $turmaActual ? [
                'id' => $turmaActual->id,
                'nome' => $turmaActual->nome,
                'classe' => $turmaActual->classeTurnoDisciplina
                    ->cursoClasseTurno
                    ->cursoClasse
                    ->classe
                    ->nome,
                'curso' => $turmaActual->classeTurnoDisciplina
                    ->cursoClasseTurno
                    ->cursoClasse
                    ->cursoTutelado
                    ->instituicaoCurso
                    ->curso
                    ->nome,
            ] : null,
        ];
    }
}
