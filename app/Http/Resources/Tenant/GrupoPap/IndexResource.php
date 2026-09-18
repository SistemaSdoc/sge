<?php

namespace App\Http\Resources\Tenant\GrupoPap;

use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class IndexResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        $cursoClasseTurno = $this->turma?->cursoClasseTurno;
        $cursoClasse = $cursoClasseTurno?->cursoClasse;
        $cursoTutelado = $cursoClasse?->cursoTutelado;
        $instituicaoCurso = $cursoTutelado?->instituicaoCurso;

        return [
            'id' => $this->id,
            'cross_tenant' => (bool) ($this->cross_tenant ?? false),
            'nome_grupo' => $this->nome_grupo,
            'tema_grupo' => $this->tema_grupo,
            'estudo_caso' => $this->estudo_caso,
            'nota_final' => $this->nota_final,
            'data_defesa' => $this->data_defesa,
            'professor' => $this->professor ? [
                'id' => $this->professor->id,
                'user_id' => $this->professor->user_id,
                'is_current_user' => $user?->is($this->professor->user),
                'can_view' => $user?->can('view', $this->professor),
                'nome' => $this->professor->user?->nome,
            ] : null,
            'instituicao' => [
                'id' => $instituicaoCurso?->instituicao?->id,
                'nome' => $instituicaoCurso?->instituicao?->nome,
            ],
            'cursoTutelado' => [
                'id' => $cursoTutelado?->id,
                'nome' => $instituicaoCurso?->curso?->nome,
            ],
            'cursoClasse' => [
                'id' => $cursoClasse?->id,
                'nome' => $cursoClasse?->classe?->nome,
            ],
            'cursoClasseTurno' => [
                'id' => $cursoClasseTurno?->id,
                'nome' => $cursoClasseTurno?->turno?->nome,
            ],
            'turma' => [
                'id' => $this->turma?->id,
                'nome' => $this->turma?->nome,
            ],
            'num_elementos' => $this->elementos->count(),
            'elementos' => $this->elementos->map(fn ($el) => [
                'id' => $el->aluno->id,
                'user_id' => $el->aluno->user_id,
                'is_current_user' => $user?->is($el->aluno->user),
                'can_view' => $user?->can('view', $el->aluno),
                'nome' => $el->aluno?->inscricao?->candidato?->nome,
            ])->filter(fn ($el) => $el['nome'])->values(),
            'can' => [
                'view' => $this->can['view'] ?? false,
                'update' => $this->can['update'] ?? false,
                'delete' => $this->can['delete'] ?? false,
                'definirData' => $this->can['definirData'] ?? false,
            ],
        ];
    }
}
