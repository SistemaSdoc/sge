<?php

namespace App\Services\Tenant;

use App\Models\Tenant\GrupoPap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TemaPapUnicidadeService
{
    public function normalizarTitulo(string $titulo): string
    {
        return mb_strtolower((string) preg_replace('/\s+/u', ' ', trim($titulo)));
    }

    public function temaExiste(
        string $cursoTuteladoId,
        string $anoLectivoId,
        string $cursoClasseTurnoId,
        string $titulo,
        ?string $grupoPapId = null,
    ): bool {
        return $this->gruposDoCursoNoAno($cursoTuteladoId, $anoLectivoId, $cursoClasseTurnoId)
            ->whereNotNull('tema_grupo')
            ->when($grupoPapId, fn ($query) => $query->whereKeyNot($grupoPapId))
            ->pluck('tema_grupo')
            ->contains(fn (string $tituloExistente): bool => $this->normalizarTitulo($tituloExistente) === $this->normalizarTitulo($titulo));
    }

    public function mesmoTemaEEstudoCasoNoutroTurno(
        string $cursoTuteladoId,
        string $anoLectivoId,
        string $cursoClasseTurnoId,
        string $titulo,
        ?string $estudoCaso,
        ?string $grupoPapId = null,
    ): bool {
        return $this->gruposDoCursoNoAno($cursoTuteladoId, $anoLectivoId)
            ->whereNotNull('tema_grupo')
            ->when($grupoPapId, fn ($query) => $query->whereKeyNot($grupoPapId))
            ->with('turma:id,curso_classe_turno_id')
            ->get(['id', 'turma_id', 'tema_grupo', 'estudo_caso'])
            ->contains(fn (GrupoPap $grupo): bool => (string) $grupo->turma?->curso_classe_turno_id !== $cursoClasseTurnoId
                && $this->normalizarTitulo((string) $grupo->tema_grupo) === $this->normalizarTitulo($titulo)
                && $this->normalizarTitulo((string) $grupo->estudo_caso) === $this->normalizarTitulo($estudoCaso));
    }

    /**
     * @return Collection<int, string>
     */
    public function titulosUsadosNoTurno(
        string $cursoTuteladoId,
        string $anoLectivoId,
        string $cursoClasseTurnoId,
        ?string $grupoPapId = null,
    ): Collection {
        return $this->gruposDoCursoNoAno($cursoTuteladoId, $anoLectivoId, $cursoClasseTurnoId)
            ->whereNotNull('tema_grupo')
            ->when($grupoPapId, fn ($query) => $query->whereKeyNot($grupoPapId))
            ->pluck('tema_grupo')
            ->map(fn (string $titulo): string => $this->normalizarTitulo($titulo));
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function validarUnicidade(
        array $dados,
        string $cursoTuteladoId,
        string $anoLectivoId,
        string $cursoClasseTurnoId,
        ?string $grupoPapId = null,
        string $campoTema = 'tema_grupo',
    ): void {
        $erros = [];
        $titulo = $dados['tema_grupo'] ?? null;
        $estudoCaso = $dados['estudo_caso'] ?? null;

        if (filled($titulo) && $this->temaExiste(
            $cursoTuteladoId,
            $anoLectivoId,
            $cursoClasseTurnoId,
            (string) $titulo,
            $grupoPapId,
        )) {
            $erros[$campoTema] = 'Este tema já foi escolhido por outro grupo neste turno e ano lectivo.';
        }

        if (
            filled($titulo)
            && $this->mesmoTemaEEstudoCasoNoutroTurno(
                $cursoTuteladoId,
                $anoLectivoId,
                $cursoClasseTurnoId,
                (string) $titulo,
                filled($estudoCaso) ? (string) $estudoCaso : null,
                $grupoPapId,
            )
        ) {
            $erros['estudo_caso'] = 'Este tema já está associado ao mesmo estudo de caso noutro turno. Indique um estudo de caso diferente.';
        }

        if ($erros !== []) {
            throw ValidationException::withMessages($erros);
        }
    }

    private function gruposDoCursoNoAno(
        string $cursoTuteladoId,
        string $anoLectivoId,
        ?string $cursoClasseTurnoId = null,
    ): Builder {
        return GrupoPap::query()
            ->whereHas('turma', function (Builder $query) use ($anoLectivoId, $cursoClasseTurnoId): void {
                $query->where('ano_lectivo_id', $anoLectivoId)
                    ->when($cursoClasseTurnoId, fn (Builder $turmas) => $turmas->where('curso_classe_turno_id', $cursoClasseTurnoId));
            })
            ->whereHas(
                'turma.cursoClasseTurno.cursoClasse',
                fn (Builder $query) => $query->where('curso_tutelado_id', $cursoTuteladoId)
            );
    }
}
