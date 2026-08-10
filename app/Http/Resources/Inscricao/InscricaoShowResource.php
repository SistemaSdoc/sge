<?php

namespace App\Http\Resources\Inscricao;

use Illuminate\Http\Resources\Json\JsonResource;

class InscricaoShowResource extends JsonResource
{
    private function extractFiliacaoPart(int $index): ?string
    {
        $filiacao = $this->candidato?->filiacao;

        if (! is_string($filiacao) || trim($filiacao) === '') {
            return null;
        }

        $parts = array_filter(array_map('trim', preg_split('/\s+e\s+/i', $filiacao)));

        return $parts[$index] ?? null;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('d/m/Y'),
            'nota_teste' => $this->nota_teste,
            'ano_lectivo' => $this->anoLectivo?->nome,
            'candidato' => [
                'nome' => $this->candidato?->nome,
                'bi' => $this->candidato?->bi,
                'numero_estudante' => $this->candidato?->numero_estudante,
                'email' => $this->candidato?->email,
                'telefone' => $this->candidato?->telefone,
                'morada' => $this->candidato?->morada,
                'nacionalidade' => $this->candidato?->nacionalidade,
                'naturalidade' => $this->candidato?->naturalidade,
                'filiacao' => $this->candidato?->filiacao,
                'nome_pai' => $this->extractFiliacaoPart(0),
                'nome_mae' => $this->extractFiliacaoPart(1),
                'data_nascimento' => $this->candidato?->data_nascimento,
            ],
            'curso' => $this->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome,
            'instituicao' => $this->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->instituicao?->nome,
            'turno' => $this->cursoClasseTurno?->turno?->nome,
        ];
    }
}
