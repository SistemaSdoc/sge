<?php

namespace App\Services\Tenant\Pauta\Index;

use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Services\Tenant\Pauta\Index\Data\PautaIndexContext;
use App\Services\Tenant\Pauta\Index\Data\PautaIndexData;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Consulta instituições, cursos e turmas do tenant actual.
 */
final class LocalPautaIndexProvider
{
    /**
     * Injeta o mapper usado para preparar as linhas das turmas.
     */
    public function __construct(private readonly PautaIndexTurmaMapper $turmaMapper) {}

    /**
     * Obtém os dados locais que correspondem ao contexto autorizado.
     */
    public function obter(User $user, PautaIndexContext $contexto): PautaIndexData
    {
        $instituicoes = $this->obterInstituicao($contexto->instituicaoId);

        if ($contexto->instituicaoIdFiltro !== $contexto->instituicaoId) {
            return new PautaIndexData($instituicoes, collect(), collect());
        }

        $cursosLocais = $this->consultarCursos($contexto);

        return new PautaIndexData(
            instituicoes: $instituicoes,
            cursos: $this->mapearCursos($cursosLocais),
            turmas: $this->obterTurmas($user, $contexto, $cursosLocais->modelKeys()),
        );
    }

    /**
     * Obtém a instituição do tenant actual para a lista de filtros.
     *
     * @return Collection<int, array{id: string, nome: string}>
     */
    private function obterInstituicao(string $instituicaoId): Collection
    {
        $instituicao = Instituicao::query()->find($instituicaoId);

        if ($instituicao === null) {
            return collect();
        }

        return collect([[
            'id' => (string) $instituicao->getKey(),
            'nome' => $instituicao->nome,
        ]]);
    }

    /**
     * Consulta os modelos dos cursos locais permitidos ao utilizador.
     *
     * @return EloquentCollection<int, CursoTutelado>
     */
    private function consultarCursos(PautaIndexContext $contexto): EloquentCollection
    {
        /** @var EloquentCollection<int, CursoTutelado> $cursosLocais */
        $cursosLocais = CursoTutelado::query()
            ->where(function ($query) use ($contexto): void {
                $query->where('instituicao_tutora_id', $contexto->instituicaoId)
                    ->orWhereHas(
                        'instituicaoCurso',
                        fn ($query) => $query->where('instituicao_id', $contexto->instituicaoId),
                    );
            })
            ->when($contexto->isProfessor && ! $contexto->isCourseSecretary, fn ($query) => $query->whereHas(
                'professores',
                fn ($query) => $query->where('professor_id', $contexto->professorId),
            ))
            ->when($contexto->isCourseSecretary, fn ($query) => $query->whereIn('id', $contexto->secretariadoCourseIds))
            ->with([
                'instituicaoCurso.curso:id,nome',
                'instituicaoCurso.instituicao:id,nome',
            ])
            ->get();

        return $cursosLocais;
    }

    /**
     * Converte os cursos locais para as linhas devolvidas à listagem.
     *
     * @param  EloquentCollection<int, CursoTutelado>  $cursosLocais
     * @return Collection<int, array{id: string, curso_key: string, nome: string, remote: bool}>
     */
    private function mapearCursos(EloquentCollection $cursosLocais): Collection
    {
        $cursos = collect();

        foreach ($cursosLocais as $cursoTutelado) {
            $cursos->push([
                'id' => (string) $cursoTutelado->id,
                'curso_key' => (string) ($cursoTutelado->instituicaoCurso?->curso_id ?? $cursoTutelado->getKey()),
                'nome' => $cursoTutelado->instituicaoCurso?->curso?->nome ?? 'Curso sem nome',
                'remote' => false,
            ]);
        }

        return $cursos;
    }

    /**
     * Obtém e formata as turmas locais dos cursos seleccionados.
     *
     * @return Collection<int, array{id: string, _created_at: int, nome: string, classe: string|null, turno: string|null, curso: string, curso_tutelado_id: string, curso_key: string, can: array{view_pauta: bool}}>
     */
    private function obterTurmas(User $user, PautaIndexContext $contexto, array $idsCursos): Collection
    {
        /** @var EloquentCollection<int, Turma> $turmasLocais */
        $turmasLocais = Turma::query()
            ->whereHas(
                'cursoClasseTurno.cursoClasse',
                fn ($query) => $query->whereIn('curso_tutelado_id', $idsCursos),
            )
            ->where('ano_lectivo_id', $contexto->anoLectivoId)
            ->when(filled($contexto->cursoTuteladoIdFiltro), fn ($query) => $query->whereHas(
                'cursoClasseTurno.cursoClasse',
                fn ($query) => $query->where('curso_tutelado_id', $contexto->cursoTuteladoIdFiltro),
            ))
            ->when($contexto->isProfessor && ! $contexto->isCourseSecretary, fn ($query) => $query->where(function ($query) use ($contexto): void {
                $query->whereHas('professores', fn ($query) => $query->where('professor_id', $contexto->professorId))
                    ->orWhereHas(
                        'cursoClasseTurno.cursoClasse',
                        fn ($query) => $query->whereIn('curso_tutelado_id', $contexto->idsCursosCoordenados),
                    );
            }))
            ->with($this->turmaMapper->relacoesNecessarias())
            ->latest('created_at')
            ->get();

        $turmas = collect();

        foreach ($turmasLocais as $turma) {
            $turmas->push($this->turmaMapper->mapear($turma, $user));
        }

        return $turmas;
    }
}
