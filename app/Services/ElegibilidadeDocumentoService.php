<?php

namespace App\Services;

use App\Models\Aluno;
use App\Models\Classe;
use App\Models\CursoClasse;
use Illuminate\Support\Collection;

class ElegibilidadeDocumentoService
{
    /**
     * Devolve as classes do curso do aluno que ainda podem ser solicitadas
     * como declaração, excluindo a classe atual/última do curso.
     */
    public function classesDisponiveisParaDeclaracao(Aluno $aluno): array
    {
        $curso = $this->obterCurso($aluno);

        if (! $curso) {
            return [];
        }

        $ultimaOrdem = $this->obterUltimaOrdemClasseDoCurso($curso);

        if ($ultimaOrdem === null) {
            return [];
        }

        $classes = $this->obterClassesDoCurso($curso)
            ->filter(fn (Classe $classe) => (int) ($classe->ordem ?? 0) < (int) $ultimaOrdem)
            ->sortBy(fn (Classe $classe) => (int) ($classe->ordem ?? 0))
            ->values()
            ->map(fn (Classe $classe) => [
                'id' => $classe->id,
                'nome' => $classe->nome,
                'ordem' => (int) ($classe->ordem ?? 0),
            ])
            ->all();

        return $classes;
    }

    /**
     * Permite pedir certificado apenas quando o aluno estiver na última classe
     * do curso/nível e exista realmente uma etapa final configurada.
     */
    public function podeSolicitarCertificado(Aluno $aluno): bool
    {
        $curso = $this->obterCurso($aluno);

        if (! $curso) {
            return false;
        }

        $classes = $this->obterClassesDoCurso($curso);

        if ($classes->isEmpty()) {
            return false;
        }

        $ultimaOrdem = $classes->max(fn (Classe $classe) => (int) ($classe->ordem ?? 0));

        if ($ultimaOrdem === null) {
            return false;
        }

        $classeAtual = $this->obterClasseAtual($aluno);

        if (! $classeAtual) {
            return false;
        }

        return (int) ($classeAtual->ordem ?? 0) === (int) $ultimaOrdem;
    }

    protected function obterCurso(Aluno $aluno): mixed
    {
        $inscricao = $aluno->inscricao;

        if (! $inscricao) {
            return null;
        }

        $cursoClasseTurno = $inscricao->cursoClasseTurno;

        if (! $cursoClasseTurno) {
            return null;
        }

        $cursoClasse = $cursoClasseTurno->cursoClasse;

        if (! $cursoClasse) {
            return null;
        }

        return $cursoClasse->cursoTutelado?->instituicaoCurso?->curso;
    }

    protected function obterClasseAtual(Aluno $aluno): ?Classe
    {
        $turmaAtual = $aluno->turmaActual()->first();

        if ($turmaAtual?->classe) {
            return $turmaAtual->classe;
        }

        $inscricao = $aluno->inscricao;

        if (! $inscricao) {
            return null;
        }

        $cursoClasseTurno = $inscricao->cursoClasseTurno;

        return $cursoClasseTurno?->cursoClasse?->classe;
    }

    protected function obterUltimaOrdemClasseDoCurso(mixed $curso): ?int
    {
        $classes = $this->obterClassesDoCurso($curso);

        if ($classes->isEmpty()) {
            return null;
        }

        return $classes->max(fn (Classe $classe) => (int) ($classe->ordem ?? 0));
    }

    protected function obterClassesDoCurso(mixed $curso): Collection
    {
        if (! $curso) {
            return collect();
        }

        $cursoClasses = CursoClasse::query()
            ->whereHas('cursoTutelado.instituicaoCurso', function ($query) use ($curso) {
                $query->where('curso_id', $curso->id);
            })
            ->with('classe')
            ->get();

        return $cursoClasses
            ->map(fn (CursoClasse $cursoClasse) => $cursoClasse->classe)
            ->filter()
            ->filter(fn (Classe $classe) => filled($classe->ordem));
    }
}
