<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Aluno;
use App\Models\Tenant\TurmaAluno;
use App\Services\Tenant\AnoLectivo\AnoLectivoResolverService;

class GrelhaCurricularService
{
    public function __construct(private readonly AnoLectivoResolverService $anoLectivoResolverService) {}

    public function gerarGrelhaCurricular(Aluno $aluno, ?string $classeId = null)
    {
        $turmaAluno = $this->obterTurmaAlunoDaClasse($aluno, $classeId);

        if (! $turmaAluno) {
            return collect();
        }

        $turma = $turmaAluno->turma;

        return $turma->cursoClasseTurno
            ->classeTurnoDisciplinas()
            ->where('ano_lectivo_id', $turma->ano_lectivo_id)
            ->with([
                'disciplina:id,nome,sigla',
                'turmaDisciplinaProfessores' => fn ($query) => $query
                    ->where('turma_id', $turma->id)
                    ->with('professor.user:id,nome'),
            ])
            ->get()
            ->filter(fn ($classeTurnoDisciplina) => $classeTurnoDisciplina->disciplina)
            ->map(fn ($classeTurnoDisciplina) => [
                'sigla' => $classeTurnoDisciplina->disciplina->sigla,
                'disciplina' => $classeTurnoDisciplina->disciplina->nome,
                'professor' => $classeTurnoDisciplina->turmaDisciplinaProfessores->first()?->professor?->user?->nome
                    ?? 'Sem professor',
            ])
            ->values();
    }

    public function classesDisponiveis(Aluno $aluno): array
    {
        return app(NotaAlunoService::class)->classesDisponiveis($aluno);
    }

    private function obterTurmaAlunoDaClasse(Aluno $aluno, ?string $classeId = null)
    {
        $query = TurmaAluno::query()
            ->where('aluno_id', $aluno->id)
            ->with(['turma.anoLectivo', 'turma.cursoClasseTurno.cursoClasse.classe']);

        if ($classeId) {
            $query->whereHas('turma.cursoClasseTurno.cursoClasse.classe', function ($q) use ($classeId) {
                $q->where('classes.id', $classeId);
            });
        }

        return $query
            ->orderByDesc('activo')
            ->orderByDesc('created_at')
            ->first();
    }
}
