<?php

namespace App\Http\Controllers\Colegios;

use App\Helpers\ArredondamentoHelper;
use App\Http\Controllers\Controller;
use App\Models\ClasseTurnoDisciplina;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use App\Models\Nota;
use App\Models\Turma;
use App\Models\TurmaAluno;
use App\Models\TurmaDisciplinaProfessor;
use App\Services\NotaService;
use App\Services\Pauta\PautaService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotaDisciplinaController extends Controller
{
    public function __construct(
        private readonly NotaService $notaService,
        private readonly PautaService $pautaService,
    ) {}

    /**
     * Lista as notas dos alunos de uma turma numa disciplina.
     */
    public function index(
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        ClasseTurnoDisciplina $classeTurnoDisciplina
    ) {

        /*
        |--------------------------------------------------------------------------
        | Validar a hierarquia da tutela
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | Buscar o vínculo Professor + Disciplina + Turma
        |--------------------------------------------------------------------------
        */

        $tdp = TurmaDisciplinaProfessor::with([
            'classeTurnoDisciplina.disciplina',
        ])
            ->where('turma_id', $turma->id)
            ->where(
                'classe_turno_disciplina_id',
                $classeTurnoDisciplina->id
            )
            ->firstOrFail();
        $periodosLancados = $this->notaService->periodosLancados($tdp->id);
        $periodosDisponiveis = $this->notaService->periodosDisponiveis($tdp->id);
        $todosDisponiveis = Auth::user()->hasAnyRole(['Director', 'Subdirector'])
            || (
                $periodosLancados[1]
                && $periodosLancados[2]
                && $periodosLancados[3]
            );

        /*
        |--------------------------------------------------------------------------
        | Buscar alunos da turma
        |--------------------------------------------------------------------------
        */

        $turmaAlunos = TurmaAluno::query()
            ->select('turma_aluno.*')
            ->join('alunos', 'alunos.id', '=', 'turma_aluno.aluno_id')
            ->join('inscricoes', 'inscricoes.id', '=', 'alunos.inscricao_id')
            ->join('candidatos', 'candidatos.id', '=', 'inscricoes.candidato_id')
            ->with([
                'aluno.inscricao.candidato:id,nome',
                'notas' => fn ($q) => $q->where(
                    'turma_disciplina_professor_id',
                    $tdp->id
                ),
            ])
            ->where('turma_aluno.turma_id', $turma->id)
            ->where('turma_aluno.situacao', 'activo')
            ->where('turma_aluno.activo', true)
            ->orderBy('candidatos.nome')
            ->paginate(
                20,
                ['*'],
                'page_alunos'
            );

        /*
        |--------------------------------------------------------------------------
        | Retornar página
        |--------------------------------------------------------------------------
        */

        return Inertia::render(
            'colegio/cursos-tutelados/classes/turnos/turmas/disciplinas/notas/index',
            [

                /*
                |--------------------------------------------------------------------------
                | Instituto tutor
                |--------------------------------------------------------------------------
                */

                'instituicao' => [
                    'id' => $instituicao->id,
                    'nome' => $instituicao->nome,
                ],

                /*
                |--------------------------------------------------------------------------
                | Colégio tutelado
                |--------------------------------------------------------------------------
                */

                'colegio' => [
                    'id' => $colegio,
                ],

                /*
                |--------------------------------------------------------------------------
                | Curso tutelado
                |--------------------------------------------------------------------------
                */

                'cursoTutelado' => [
                    'id' => $cursoTutelado->id,
                ],

                /*
                |--------------------------------------------------------------------------
                | Curso Classe
                |--------------------------------------------------------------------------
                */

                'cursoClasse' => [
                    'id' => $cursoClasse->id,
                ],

                /*
                |--------------------------------------------------------------------------
                | Turno
                |--------------------------------------------------------------------------
                */

                'cursoClasseTurno' => [
                    'id' => $cursoClasseTurno->id,
                ],

                /*
                |--------------------------------------------------------------------------
                | Turma
                |--------------------------------------------------------------------------
                */

                'turma' => [
                    'id' => $turma->id,
                    'nome' => $turma->nome,
                ],

                /*
                |--------------------------------------------------------------------------
                | TDP
                |--------------------------------------------------------------------------
                */

                'tdp' => $tdp->id,

                /*
                |--------------------------------------------------------------------------
                | Disciplina
                |--------------------------------------------------------------------------
                */

                'disciplina' => [
                    'id' => $classeTurnoDisciplina->id,
                    'sigla' => $tdp
                        ->classeTurnoDisciplina
                        ->disciplina
                        ->sigla,

                    'nome' => $tdp
                        ->classeTurnoDisciplina
                        ->disciplina
                        ->nome,
                ],

                /*
                |--------------------------------------------------------------------------
                | Permissões
                |--------------------------------------------------------------------------
                */

                'can' => [
                    'create' => Auth::user()->can(
                        'create',
                        [Nota::class, $tdp]
                    ),

                    'export' => Auth::user()->can(
                        'export',
                        [Nota::class, $tdp]
                    ),

                    'overrideLockedPeriods' => Auth::user()->hasAnyRole(
                        ['Director', 'Subdirector']
                    ),
                ],

                /*
                |--------------------------------------------------------------------------
                | Alunos
                |--------------------------------------------------------------------------
                */

                'alunos' => [
                    'data' => $turmaAlunos
                        ->getCollection()
                        ->map(fn ($ta) => [

                            'turma_aluno_id' => $ta->id,

                            'aluno_id' => $ta->aluno->id,

                            'nome' => $ta
                                ->aluno
                                ->inscricao
                                ?->candidato
                                ?->nome,

                            'notas' => $ta
                                ->notas
                                ->map(
                                    fn ($n) => $this->formatarNota($n)
                                )
                                ->keyBy('periodo'),
                        ])
                        ->values(),

                    'current_page' => $turmaAlunos->currentPage(),

                    'last_page' => $turmaAlunos->lastPage(),
                ],

                'periodos_lancados' => $periodosLancados,
                'periodos_disponiveis' => $periodosDisponiveis,
                'todos_disponiveis' => $todosDisponiveis,
            ]
        );
    }

    private function formatarNota(Nota $n): array
    {
        return [
            'id' => $n->id,
            'periodo' => $n->periodo,
            'mac' => $n->mac,
            'nota_prova_professor' => $n->nota_prova_professor,
            'nota_prova_trimestral' => $n->nota_prova_trimestral,
            'media_trimestral' => ArredondamentoHelper::roundToHalf($n->media_trimestral),
            'media_final' => ArredondamentoHelper::roundToHalf($n->media_final),
            'faltas' => $n->faltas,
            'situacao_trimestral' => $n->situacao_trimestral,
            'situacao_anual' => $n->situacao_anual,
        ];
    }
}
