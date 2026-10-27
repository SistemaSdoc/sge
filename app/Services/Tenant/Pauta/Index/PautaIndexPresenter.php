<?php

namespace App\Services\Tenant\Pauta\Index;

use App\Models\Central\AnoLectivo;
use App\Services\Tenant\Pauta\Index\Data\PautaIndexContext;
use App\Services\Tenant\Pauta\Index\Data\PautaIndexData;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Combina os dados das fontes e prepara as props da página Inertia.
 */
final class PautaIndexPresenter
{
    /**
     * Combina instituições/cursos/turmas e devolve o payload da listagem.
     *
     * @return array<string, mixed>
     */
    public function apresentar(
        Request $request,
        PautaIndexContext $contexto,
        PautaIndexData $dadosLocais,
        PautaIndexData $dadosRemotos,
    ): array {
        $instituicoes = $dadosLocais->instituicoes
            ->merge($dadosRemotos->instituicoes)
            ->unique('id')
            ->sortBy('nome')
            ->values();

        abort_unless($instituicoes->contains('id', $contexto->instituicaoIdFiltro), 404);

        $cursos = $dadosLocais->cursos
            ->merge($dadosRemotos->cursos)
            ->unique(fn (array $curso): string => (string) ($curso['curso_key'] ?? $curso['id']))
            ->sortBy('nome')
            ->values();

        /** @var array{id: string, curso_key: string, nome: string, remote: bool}|null $cursoSelecionado */
        $cursoSelecionado = filled($contexto->cursoTuteladoIdFiltro)
            ? $cursos->firstWhere('id', (string) $contexto->cursoTuteladoIdFiltro)
            : null;

        $cursoTuteladoIdFiltro = $cursoSelecionado === null ? null : (string) $cursoSelecionado['id'];

        $turmas = $this->filtrarTurmas(
            $dadosLocais->turmas->merge($dadosRemotos->turmas),
            $contexto,
            $cursoSelecionado['curso_key'] ?? null,
        );

        return [
            'instituicao' => $instituicoes->firstWhere('id', $contexto->instituicaoIdFiltro),
            'instituicoes' => $instituicoes,
            'cursos' => $cursos,
            'turmas' => $this->paginarTurmas($turmas, $request),
            'anosLectivos' => AnoLectivo::all(),
            'filtros' => [
                'instituicao_id' => $contexto->instituicaoIdFiltro,
                'curso_tutelado_id' => $cursoTuteladoIdFiltro,
                'ano_lectivo_id' => $contexto->anoLectivoId,
                'search' => $contexto->filtros['search'] ?? '',
            ],
        ];
    }

    /**
     * Aplica o filtro de curso e pesquisa, ordena e remove campos internos.
     *
     * @param  Collection<int, array{id: string, _created_at: int, nome: string, classe: string|null, turno: string|null, curso: string, curso_tutelado_id: string, curso_key: string, can: array{view_pauta: bool}}>  $turmas
     * @return Collection<int, array{id: string, _created_at: int, nome: string, classe: string|null, turno: string|null, curso: string, curso_tutelado_id: string, curso_key: string, can: array{view_pauta: bool}}>
     */
    private function filtrarTurmas(
        Collection $turmas,
        PautaIndexContext $contexto,
        mixed $cursoKeyFiltro
    ): Collection {
        $filtros = $contexto->filtros;

        $turmas = $turmas->when(
            $cursoKeyFiltro,
            fn (Collection $items): Collection => $items->where('curso_key', $cursoKeyFiltro),
        );

        if (filled($filtros['search'] ?? null)) {
            $term = mb_strtolower(trim((string) $filtros['search']));

            $turmas = $turmas->filter(fn (array $turma): bool => collect([
                $turma['nome'] ?? '',
                $turma['classe'] ?? '',
                $turma['turno'] ?? '',
                $turma['curso'] ?? '',
            ])->contains(
                fn ($value): bool => str_contains(mb_strtolower((string) $value), $term),
            ));
        }

        // A data é necessária para ordenar resultados de origens locais e remotas em conjunto.
        return $turmas
            ->sortByDesc('_created_at')
            ->map(function (array $turma): array {
                unset($turma['_created_at']);

                return $turma;
            })
            ->values();
    }

    /**
     * Pagina as turmas e preserva os restantes parâmetros da consulta.
     *
     * @param  Collection<int, array<string, mixed>>  $turmas
     */
    private function paginarTurmas(Collection $turmas, Request $request): LengthAwarePaginator
    {
        $porPagina = min(100, max(1, $request->integer('per_page', 10)));

        $pagina = max(1, $request->integer('page', 1));

        $turmasPaginadas = new LengthAwarePaginator(
            $turmas->forPage($pagina, $porPagina)->values(),
            $turmas->count(),
            $porPagina,
            $pagina,
            ['path' => $request->url()],
        );

        $turmasPaginadas->appends($request->except('page'));

        return $turmasPaginadas;
    }
}
