<?php

namespace App\Http\Resources\GrupoPap;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IndexResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome_grupo' => $this->nome_grupo,
            'tema_grupo' => $this->tema_grupo,
            'estudo_caso' => $this->estudo_caso,
            'nota_final' => $this->nota_final,
            'data_defesa' => $this->data_defesa,
            'professor' => $this->professor ? [
                'id' => $this->professor->id,
                'nome' => $this->professor->user?->nome,
            ] : null,
            'turma' => $this->turma?->nome,
            'classe' => $this->turma?->cursoClasseTurno?->cursoClasse?->classe?->nome,
            'curso' => $this->turma?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome,
            'instituicao' => $this->turma?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->instituicao?->nome,
            'num_elementos' => $this->elementos->count(),
            'elementos' => $this->elementos->map(fn ($el) => [
                'id' => $el->aluno->id,
                'nome' => $el->aluno?->inscricao?->candidato?->nome,
            ])->filter(fn ($el) => $el['nome'])->values(),
        ];
    }
}
