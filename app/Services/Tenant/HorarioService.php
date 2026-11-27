<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Aluno;
use App\Models\Tenant\ClasseTurnoDisciplinaHorario;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaDisciplinaProfessor;
use Illuminate\Support\Collection;

class HorarioService
{
    /**
     * Horário do professor: só as turmas onde foi atribuído, no ano lectivo.
     */
    public function doProfessor(Professor $professor, $anoLectivoId): Collection
    {
        return $professor->turmaDisciplinaProfessor()
            ->whereHas('turma', fn ($q) => $q->where('ano_lectivo_id', $anoLectivoId))
            ->with([
                'classeTurnoDisciplina.disciplina',
                'classeTurnoDisciplina.horarios',
                'turma',
                'turma.cursoClasseTurno.turno',
                'turma.cursoClasseTurno.cursoClasse.classe',
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
            ])
            ->get()
            ->flatMap(function ($tdp) {
                $disciplina = $tdp->classeTurnoDisciplina;
                $turma = $tdp->turma;

                return $disciplina->horarios
                    ->where('turma_id', $turma->id) // só esta turma, sem fallback null
                    ->map(fn ($horario) => $this->formatar($horario, $disciplina, $turma));
            })
            ->values();
    }

    /**
     * Horário do aluno: turmas do ano lectivo, com o professor de cada disciplina.
     */
    public function doAluno(Aluno $aluno, $anoLectivoId): Collection
    {
        $turmaIds = $aluno->turmas()
            ->wherePivot('ano_lectivo_id', $anoLectivoId)
            ->pluck((new Turma)->getQualifiedKeyName());

        $horarios = ClasseTurnoDisciplinaHorario::query()
            ->whereIn('turma_id', $turmaIds)
            ->with([
                'turma.cursoClasseTurno.turno',
                'turma.cursoClasseTurno.cursoClasse.classe',
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
                'classeTurnoDisciplina.disciplina',
            ])
            ->get();

        // Professor de cada par turma + disciplina, numa só query
        $professores = TurmaDisciplinaProfessor::query()
            ->whereIn('turma_id', $turmaIds)
            ->whereNotNull('professor_id')
            ->with('professor.user') // AJUSTAR: onde está o nome do professor
            ->get()
            ->keyBy(fn ($tdp) => $tdp->turma_id.'|'.$tdp->classe_turno_disciplina_id);

        return $horarios->map(function ($horario) use ($professores) {
            $tdp = $professores->get($horario->turma_id.'|'.$horario->classe_turno_disciplina_id);

            return $this->formatar(
                $horario,
                $horario->classeTurnoDisciplina,
                $horario->turma,
                $tdp?->professor?->user?->nome,
            );
        })->values();
    }

    private function formatar($horario, $classeTurnoDisciplina, $turma, ?string $professor = null): array
    {
        $cursoClasseTurno = $turma->cursoClasseTurno;

        return [
            'id' => $horario->id,
            'dia_semana' => (int) $horario->dia_semana,
            'hora_inicio' => $this->formatarHora($horario->hora_inicio),
            'hora_fim' => $this->formatarHora($horario->hora_fim),
            'disciplina' => [
                'nome' => $classeTurnoDisciplina->disciplina->nome,
                'sigla' => $classeTurnoDisciplina->disciplina->sigla,
            ],
            'turma' => [
                'id' => $turma->id,
                'nome' => $turma->nome,
                'curso' => $cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome,
                'classe' => $cursoClasseTurno?->cursoClasse?->classe?->nome,
                'turno' => $cursoClasseTurno?->turno?->nome,
            ],
            'sala' => $turma->sala,
            'professor' => $professor,
        ];
    }

    private function formatarHora($valor): string
    {
        return $valor instanceof \DateTimeInterface
            ? $valor->format('H:i')
            : substr((string) $valor, 0, 5);
    }
}
