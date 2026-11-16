<?php

namespace App\Http\Resources\Tenant\Pap\Grupo;

use App\Models\Tenant\BancaJuriPap;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GrupoPapDetalhePageResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Prepara todos os dados da página de detalhe do grupo PAP.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource['user'];

        /** @var GrupoPap $grupoPap */
        $grupoPap = $this->resource['grupoPap'];

        /** @var Instituicao|null $instituicaoTutora */
        $instituicaoTutora = $this->resource['instituicaoTutora'];

        $isReadOnlyCourseSecretary = $user->hasRole('Secretario do Curso');

        $can = fn (string $ability): bool => ! $isReadOnlyCourseSecretary && $user->can($ability, $grupoPap);

        return [
            'instituicao' => $this->resource['instituicao']->only('id', 'nome'),
            'cursoTutelado' => $this->resource['cursoTutelado']->only('id'),
            'cursoClasse' => $this->resource['cursoClasse']->only('id'),
            'cursoClasseTurno' => $this->resource['cursoClasseTurno']->only('id'),
            'turma' => $this->resource['turma']->only('id', 'nome'),
            'anoLectivoId' => $this->resource['turma']->ano_lectivo_id,
            'anosLectivos' => $this->resource['anosLectivos'],
            'grupoPap' => (new GrupoPapDetalheResource($grupoPap))->resolve($request),
            'currentUserId' => (string) $user->getKey(),
            'historico' => $this->resource['historico'],
            'trabalho' => $this->resource['trabalho'],
            'banca' => BancaJuriPapResource::collection($this->resource['detalhes']['banca']),
            'elementos' => ElementoGrupoPapResource::collection($this->resource['detalhes']['elementos']),
            'can' => [
                'update' => $can('update'),
                'definirData' => $can('definirData'),
                'delete' => $can('delete'),
                'corrigirTema' => $can('corrigirTema'),
                'aprovar' => $can('aprovar'),
                'reprovar' => $can('reprovar'),
                'solicitarMelhoria' => $can('solicitarMelhoria'),
                'definirTema' => $can('definirTema'),
                'aprovarComoTutor' => $can('aprovarComoTutor'),
                'submeter' => $can('submeterTrabalho'),
                'aprovarTrabalhoComoTutor' => $can('aprovarTrabalhoComoTutor'),
                'solicitarCorrecaoComoTutor' => $can('solicitarCorrecaoTrabalhoComoTutor'),
                'aprovarComoCoordenacao' => $can('aprovarTrabalhoComoCoordenacao'),
                'solicitarCorrecaoComoCoordenacao' => $can('solicitarCorrecaoTrabalhoComoCoordenacao'),
                'downloadVersao' => $can('downloadVersaoTrabalho'),
                'elementos' => [
                    'create' => ! $isReadOnlyCourseSecretary && $user->can('elementogrupopap.create'),
                    'atualizarNota' => $this->resource['canUpdateElementNotes'],
                    'delete' => ! $isReadOnlyCourseSecretary && $user->can('elementogrupopap.delete'),
                ],
                'verBanca' => $isReadOnlyCourseSecretary
                    || ($instituicaoTutora?->id === $user->instituicao_id && ! $user->hasRole('Aluno')),
                'banca' => [
                    'create' => ! $isReadOnlyCourseSecretary
                        && $user->can('create', [BancaJuriPap::class, $grupoPap])
                        && $instituicaoTutora?->id === $user->instituicao_id,
                    'update' => ! $isReadOnlyCourseSecretary
                        && $user->can('bancajuripap.update')
                        && $instituicaoTutora?->id === $user->instituicao_id,
                    'delete' => ! $isReadOnlyCourseSecretary
                        && $user->can('bancajuripap.delete')
                        && $instituicaoTutora?->id === $user->instituicao_id,
                ],
            ],
        ];
    }
}
