<?php

namespace App\Services\Tenant\Pauta\Exports;

use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;

/**
 * Prepara os cabeçalhos requeridos pelos templates trimestral e final.
 */
final class PautaTemplateHeaderProvider
{
    /**
     * Prepara o cabeçalho da pauta trimestral.
     *
     * @return array{institution: string, class: string, area: string, course: string, year: string, room: string, period: int, director: mixed, class_coordinator: null, course_coordinator: mixed, subdirector: mixed}
     */
    public function prepararTrimestral(Turma $turma, int $periodo): array
    {
        $cursoClasseTurno = $turma->cursoClasseTurno;

        $cursoClasse = $cursoClasseTurno?->cursoClasse;

        $cursoTutelado = $cursoClasse?->cursoTutelado;

        $classe = $cursoClasse?->classe;

        $curso = $cursoTutelado?->instituicaoCurso?->curso;

        $instituicao = $this->obterInstituicao($cursoTutelado);

        $responsaveis = $this->obterResponsaveis($instituicao, $cursoTutelado);

        return [
            'institution' => $instituicao?->nome ?? 'INSTITUTO',
            'class' => $classe?->nome ?? '',
            'area' => $curso?->nome ?? '',
            'course' => $curso?->nome ?? '',
            'year' => $turma->anoLectivo?->nome ?? '',
            'room' => $turma->sala ?? '',
            'period' => $periodo,
            'director' => $responsaveis['director'],
            'class_coordinator' => null,
            'course_coordinator' => $responsaveis['course_coordinator'],
            'subdirector' => $responsaveis['subdirector'],
        ];
    }

    /**
     * Prepara o cabeçalho da pauta final.
     *
     * @return array<string, string|null>
     */
    public function prepararFinal(Turma $turma): array
    {
        $cursoTutelado = $turma->cursoClasseTurno?->cursoClasse?->cursoTutelado;

        $instituicao = $this->obterInstituicao($cursoTutelado);

        $curso = $cursoTutelado?->instituicaoCurso?->curso;

        $classe = $turma->cursoClasseTurno?->cursoClasse?->classe;

        $responsaveis = $this->obterResponsaveis($instituicao, $cursoTutelado);

        return [
            'institution' => (string) ($instituicao?->getAttribute('nome') ?? 'INSTITUTO'),
            'class' => (string) ($classe?->getAttribute('nome') ?? ''),
            'turma' => (string) $turma->getAttribute('nome'),
            'area' => (string) ($curso?->getAttribute('nome') ?? ''),
            'course' => (string) ($curso?->getAttribute('nome') ?? ''),
            'year' => (string) ($turma->anoLectivo?->getAttribute('nome') ?? ''),
            'shift' => (string) ($turma->cursoClasseTurno?->turno?->getAttribute('nome') ?? ''),
            'room' => (string) ($turma->getAttribute('sala') ?? ''),
            'director' => $responsaveis['director'],
            'class_coordinator' => null,
            'course_coordinator' => $responsaveis['course_coordinator'],
            'subdirector' => $responsaveis['subdirector'],
        ];
    }

    /**
     * Resolve a instituição usada no cabeçalho, incluindo a instituição tutora.
     */
    private function obterInstituicao(?CursoTutelado $cursoTutelado): ?Instituicao
    {
        return $cursoTutelado?->instituicaoCurso?->instituicao
            ?? $cursoTutelado?->instituicaoTutora;
    }

    /**
     * Obtém os responsáveis oficiais da instituição e do curso tutelado.
     *
     * @return array{director: mixed, course_coordinator: mixed, subdirector: mixed}
     */
    private function obterResponsaveis(?Instituicao $instituicao, ?CursoTutelado $cursoTutelado): array
    {
        // A pauta usa as roles da instituição e o coordenador guardado no pivot do curso.
        $director = $instituicao?->users()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Director'))
            ->first();

        $subdirector = $instituicao?->users()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Subdirector'))
            ->first();

        $coordenadorCurso = $cursoTutelado?->professores()
            ->wherePivot('coordenador', true)
            ->with('user')
            ->first()?->user;

        return [
            'director' => $director?->nome,
            'course_coordinator' => $coordenadorCurso?->nome,
            'subdirector' => $subdirector?->nome,
        ];
    }
}
