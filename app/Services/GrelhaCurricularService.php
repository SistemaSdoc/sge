<?php

namespace App\Services;

use App\Models\Aluno;
use App\Models\TurmaAluno;
use App\Services\AnoLectivo\AnoLectivoResolverService;

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
            ->with([
                'disciplina:id,nome,sigla',
                'turmaDisciplinaProfessores' => fn ($q) => $q
                    ->where('turma_id', $turma->id)
                    ->with('professor.user:id,nome'),
            ])
            ->get()
            ->map(fn ($ctd) => [
                'sigla' => $ctd->disciplina->sigla,
                'disciplina' => $ctd->disciplina->nome,
                'professor' => $ctd->turmaDisciplinaProfessores->first()?->professor?->user?->nome
                    ?? 'Sem professor',
            ]);
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
