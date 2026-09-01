<?php

namespace App\Http\Resources\Tenant\CursoTutelado;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CursoTuteladoResourceEdit extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'curso_id' => $this->instituicaoCurso->curso_id,
            'curso' => [
                'nome' => $this->instituicaoCurso->curso->nome,
                'duracao_anos' => $this->instituicaoCurso->duracao_anos,
            ],
            'nivel_ensino_id' => (string) ($this->cursoClasses->first()?->nivel_ensino_id
                ?? $this->cursoClasses->first()?->nivelEnsino?->id
                ?? ''),
            'nivel_ensino' => $this->cursoClasses->first()?->nivelEnsino ? [
                'id' => (string) $this->cursoClasses->first()->nivelEnsino->id,
                'nome' => $this->cursoClasses->first()->nivelEnsino->nome,
            ] : null,
            'tipo_tutela' => $this->tipo_tutela ?? 'propria',
            'tenant_tutor_id' => $this->curso_tutelado_shared_id
                ? $this->cursoTuteladoShared?->tenant_tutor_id
                : null,
            'instituicao_tutora' => $this->instituicaoTutora ? [
                'id' => $this->instituicaoTutora->id,
                'nome' => $this->instituicaoTutora->nome,
            ] : null,
            'classes' => $this->classes->pluck('id'),
        ];
    }
}
