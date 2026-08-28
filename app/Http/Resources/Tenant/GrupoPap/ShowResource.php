<?php

namespace App\Http\Resources\Tenant\GrupoPap;

use App\Models\Tenant\CursoTutelado;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'objectivos' => $this->objectivos,
            'problema' => $this->problema,
            'status_aprovacao' => $this->status_aprovacao,
            'comentario_aprovacao' => $this->comentario_aprovacao,
            'nota_final' => $this->nota_final,
            'data_defesa' => $this->data_defesa?->toIso8601String(),
            'local_defesa' => $this->local_defesa,
            'professor' => $this->professor ? [
                'id' => $this->professor->id,
                'nome' => $this->professor->user->nome,
                'email' => $this->professor->user->email,
            ] : null,
            'turma' => $this->turma ? [
                'nome' => $this->turma->nome,

            ] : null,
            'criterios_pap_url' => (function () {
                $cursoTutelado = $this->turma
                    ?->cursoClasseTurno
                    ?->cursoClasse
                    ?->cursoTutelado;

                if (! $cursoTutelado) {
                    return null;
                }

                // Usa os critérios do próprio curso tutelado
                // ou os da instituição tutora como fallback
                $path = $cursoTutelado->criterios_pap_path;

                if (! $path) {
                    // Buscar o curso_tutelado da instituição tutora
                    // para o mesmo curso
                    $path = CursoTutelado::query()
                        ->where('instituicao_tutora_id', $cursoTutelado->instituicaoTutora?->id)
                        ->whereHas(
                            'instituicaoCurso',
                            fn ($q) => $q->where('curso_id', $cursoTutelado->instituicaoCurso?->curso_id)
                                ->where('instituicao_id', $cursoTutelado->instituicaoTutora?->id)
                        )
                        ->value('criterios_pap_path');
                }

                return $path ? Storage::url($path) : null;
            })(),
            'manual_pt_url' => (function () {
                $cursoTutelado = $this->turma
                    ?->cursoClasseTurno
                    ?->cursoClasse
                    ?->cursoTutelado;

                if (! $cursoTutelado) {
                    return null;
                }

                $path = $cursoTutelado->manual_pt_path;

                if (! $path) {
                    $cursoId = $cursoTutelado->instituicaoCurso?->curso_id;
                    $tutorId = $cursoTutelado->instituicao_tutora_id;

                    $path = CursoTutelado::query()
                        ->where('instituicao_tutora_id', $tutorId)
                        ->whereHas(
                            'instituicaoCurso',
                            fn ($q) => $q->where('curso_id', $cursoId)
                                ->where('instituicao_id', $tutorId)
                        )
                        ->value('manual_pt_path');
                }

                return $path ? Storage::url($path) : null;
            })(),
            'estrutura_trabalho_pap_url' => (function () {
                $cursoTutelado = $this->turma
                    ?->cursoClasseTurno
                    ?->cursoClasse
                        ?->cursoTutelado;

                if (!$cursoTutelado)
                    return null;

                $path = $cursoTutelado->estrutura_trabalho_pap_path;

                if (!$path) {
                    $cursoId = $cursoTutelado->instituicaoCurso?->curso_id;
                    $tutorId = $cursoTutelado->instituicao_tutora_id;

                    $path = \App\Models\CursoTutelado::query()
                        ->where('instituicao_tutora_id', $tutorId)
                        ->whereHas(
                            'instituicaoCurso',
                            fn($q) =>
                            $q->where('curso_id', $cursoId)
                                ->where('instituicao_id', $tutorId)
                        )
                        ->value('estrutura_trabalho_pap_path');
                }

                return $path ? Storage::url($path) : null;
            })(),
            'aprovado_por' => $this->aprovadoPor ? [
                'id' => $this->aprovadoPor->id,
                'nome' => $this->aprovadoPor->nome ?? null,
            ] : null,
        ];
    }
}
