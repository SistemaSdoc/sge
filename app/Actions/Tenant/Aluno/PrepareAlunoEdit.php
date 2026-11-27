<?php

namespace App\Actions\Tenant\Aluno;

use App\Models\Tenant\Aluno;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Services\Tenant\AnoLectivo\AnoLectivoResolverService;

/** Prepara os dados de cursos, classes, turnos e turmas do formulário. */
class PrepareAlunoEdit
{
    public function __construct(private readonly AnoLectivoResolverService $anoLectivoResolverService) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(Aluno $aluno, User $user): array
    {
        $anoLectivoId = $this->anoLectivoResolverService->obterAnoLectivoDefault();

        $aluno->load([
            'inscricao.candidato:id,user_id,nome,bi,email',
            'inscricao.cursoClasseTurno.turno:id,nome',
            'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
            'turmas' => fn ($query) => $query->wherePivot('activo', true)
                ->with('cursoClasseTurno.cursoClasse.classe:id,nome'),
        ]);

        $turmaAtual = $aluno->turmas()->wherePivot('activo', true)->first();
        $cursoTuteladoId = $turmaAtual?->cursoClasseTurno?->cursoClasse?->curso_tutelado_id
            ?? $aluno->inscricao?->cursoClasseTurno?->cursoClasse?->curso_tutelado_id;
        $cursoClasseIdAtual = $turmaAtual?->cursoClasseTurno?->curso_classe_id
            ?? $aluno->inscricao?->cursoClasseTurno?->curso_classe_id;
        $cursoClasseTurnoIdAtual = $turmaAtual?->curso_classe_turno_id
            ?? $aluno->inscricao?->cursoClasseTurno?->id;

        $cursosQuery = CursoTutelado::query()
            ->with('instituicaoCurso.curso:id,nome,deleted_at');

        if ($user->instituicao_id) {
            $cursosQuery->whereHas(
                'instituicaoCurso',
                fn ($query) => $query->where('instituicao_id', $user->instituicao_id),
            );
        } elseif ($cursoTuteladoId) {
            $cursosQuery->whereKey($cursoTuteladoId);
        }

        if ($user->hasRole('Secretario do Curso')) {
            $cursosQuery->whereHas(
                'secretarios',
                fn ($query) => $query->whereKey($user->getKey()),
            );
        }

        $cursos = $cursosQuery->get()
            ->reject(fn (CursoTutelado $cursoTutelado) => $cursoTutelado->instituicaoCurso?->curso?->trashed())
            ->map(fn (CursoTutelado $cursoTutelado) => [
                'id' => $cursoTutelado->id,
                'nome' => $cursoTutelado->instituicaoCurso?->curso?->nome ?? 'Curso',
            ])
            ->values();

        $cursoTuteladoIds = $cursos->pluck('id');
        $classes = $cursoTuteladoIds->isNotEmpty()
            ? CursoClasse::query()
                ->whereIn('curso_tutelado_id', $cursoTuteladoIds)
                ->with('classe:id,nome')
                ->get()
                ->map(fn ($cursoClasse) => [
                    'id' => $cursoClasse->id,
                    'nome' => $cursoClasse->classe?->nome,
                    'curso_tutelado_id' => $cursoClasse->curso_tutelado_id,
                ])
            : [];

        $turnos = $cursoTuteladoIds->isNotEmpty()
            ? CursoClasseTurno::query()
                ->whereHas('cursoClasse', fn ($query) => $query->whereIn('curso_tutelado_id', $cursoTuteladoIds))
                ->with('turno:id,nome')
                ->get()
                ->map(fn ($cursoClasseTurno) => [
                    'id' => $cursoClasseTurno->id,
                    'nome' => $cursoClasseTurno->turno?->nome,
                    'curso_classe_id' => $cursoClasseTurno->curso_classe_id,
                ])
            : [];

        $turmas = $cursoTuteladoIds->isNotEmpty()
            ? Turma::query()
                ->whereHas('cursoClasseTurno.cursoClasse', fn ($query) => $query->whereIn('curso_tutelado_id', $cursoTuteladoIds))
                ->where('ano_lectivo_id', $anoLectivoId)
                ->with([
                    'cursoClasseTurno.turno:id,nome',
                    'cursoClasseTurno.cursoClasse.classe:id,nome',
                ])
                ->orderBy('nome')
                ->get()
                ->map(fn ($turma) => [
                    'id' => $turma->id,
                    'nome' => $turma->nome,
                    'curso_classe_id' => $turma->cursoClasseTurno?->curso_classe_id,
                    'curso_classe_turno_id' => $turma->curso_classe_turno_id,
                    'classe' => $turma->cursoClasseTurno?->cursoClasse?->classe?->nome,
                    'turno' => $turma->cursoClasseTurno?->turno?->nome,
                ])
            : [];

        return [
            'aluno' => [
                'id' => $aluno->id,
                'matricula' => $aluno->matricula,
                'numero_processo' => $aluno->numero_processo,
                'nome' => $aluno->inscricao?->candidato?->nome,
                'bi' => $aluno->inscricao?->candidato?->bi,
                'email' => $aluno->inscricao?->candidato?->email,
                'curso_tutelado_id' => $cursoTuteladoId,
                'curso_classe_id' => $cursoClasseIdAtual,
                'curso_classe_turno_id' => $cursoClasseTurnoIdAtual,
                'turma_id' => $turmaAtual?->id,
            ],
            'cursos' => $cursos,
            'classes' => $classes,
            'turnos' => $turnos,
            'turmas' => $turmas,
        ];
    }
}
