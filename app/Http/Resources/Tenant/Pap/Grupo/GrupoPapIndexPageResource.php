<?php

namespace App\Http\Resources\Tenant\Pap\Grupo;

use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GrupoPapIndexPageResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Prepara todos os dados da página principal de grupos PAP.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource['user'];
        $instituicao = $user->instituicao;
        $isReadOnlyCourseSecretary = $user->hasRole('Secretario do Curso');
        $instituicaoIdFiltro = $this->resource['instituicaoIdFiltro'];
        $cursoIdFiltro = $this->resource['cursoIdFiltro'];
        $anoLectivoId = $this->resource['anoLectivoId'];

        return [
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
            ],
            'instituicoes' => $this->resource['instituicoes'],
            'cursosTutelados' => $this->resource['cursosTutelados'],
            'cursosFiltro' => $this->resource['cursosFiltro'],
            'gruposPap' => [
                'data' => GrupoPapListagemResource::collection($this->resource['grupos'])->resolve($request),
            ],
            'anoLectivoId' => $anoLectivoId,
            'anosLectivos' => $this->resource['anosLectivos'],
            'filters' => [
                'search' => $this->resource['search'],
                'instituicao_id' => (string) ($instituicaoIdFiltro ?? ''),
                'curso_id' => (string) ($cursoIdFiltro ?? ''),
                'ano_lectivo_id' => (string) ($anoLectivoId ?? ''),
            ],
            'can' => [
                'create' => ! $isReadOnlyCourseSecretary && $user->can('create', GrupoPap::class),
                'selecionarInstituicao' => ($isReadOnlyCourseSecretary && $instituicao?->tipo === 'instituto')
                    || $user->can('selecionarInstituicao', GrupoPap::class),
                'selecionarTodasInstituicoes' => $isReadOnlyCourseSecretary
                    && $instituicao?->tipo === 'instituto',
                'selecionarAnoLectivo' => $isReadOnlyCourseSecretary
                    || $user->can('selecionarAnoLectivo', GrupoPap::class),
            ],
        ];
    }
}
