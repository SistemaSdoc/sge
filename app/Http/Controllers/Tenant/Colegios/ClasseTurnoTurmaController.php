<?php

namespace App\Http\Controllers\Tenant\Colegios;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlunoTurmaResource;
use App\Http\Resources\ClasseTurnoDisciplinaResource;
use App\Http\Resources\GrupoPapIndexResource;
use App\Http\Resources\Turma\TurmaShowResource;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\ClasseTurnoDisciplina;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Services\Tenant\Pauta\PautaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ClasseTurnoTurmaController extends Controller
{
    public function __construct(
        private readonly PautaService $pautaService,
    ) {}

    public function show(
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma
    ) {
        Gate::authorize('view', $turma);

        \Log::info('ClasseTurnoTurma::show', [
            'instituicao' => $instituicao?->id,
            'colegio' => $colegio,
            'cursoTutelado' => $cursoTutelado?->id,
            'cursoClasse' => $cursoClasse?->id,
            'cursoClasseTurno' => $cursoClasseTurno?->id,
            'turma' => $turma?->id,
        ]);

        $user = Auth::guard('tenant')->user();

        $anoLectivoId = request('ano_lectivo_id')
            ?? AnoLectivo::where('activo', 1)->first()?->id;

        /*
        |--------------------------------------------------------------------------
        | Validar a hierarquia da tutela
        |--------------------------------------------------------------------------
        |
        | Instituto tutor
        |      ↓
        | Curso Tutelado
        |      ↓
        | Curso Classe
        |      ↓
        | Curso Classe Turno
        |      ↓
        | Turma
        |
        */

        /*
        |--------------------------------------------------------------------------
        | Carregar dados da turma
        |--------------------------------------------------------------------------
        */

        $turma->load([
            'cursoClasseTurno.cursoClasse.classe:id,nome',
            'cursoClasseTurno.turno:id,nome',
            'anoLectivo:id,nome',
            'gruposPap:id,turma_id,nome_grupo,tema_grupo,status,nota_final',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Alunos da turma
        |--------------------------------------------------------------------------
        */

        $alunos = $turma->alunos()
            ->wherePivot('activo', true)
            ->with([
                'inscricao.candidato:id,nome',
                'user:id,email,telefone',
            ])
            ->paginate(
                10,
                ['*'],
                'page_alunos'
            );

        /*
        |--------------------------------------------------------------------------
        | Disciplinas da turma
        |--------------------------------------------------------------------------
        */

        $disciplinasQuery = $turma->cursoClasseTurno
            ->classeTurnoDisciplinas()
            ->with([
                'disciplina:id,nome,sigla',

                'turmaDisciplinaProfessores' => function ($q) use ($turma) {
                    $q->where('turma_id', $turma->id);
                },

                'turmaDisciplinaProfessores.professor.user:id,nome',

                'horarios',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Restringir disciplinas para professores
        |--------------------------------------------------------------------------
        */

        if ($user->hasRole('Professor')) {

            $professorId = $user->professor?->id;

            if (! $professorId) {
                $disciplinasQuery->whereRaw('0 = 1');
            }
        }

        $disciplinas = $disciplinasQuery
            ->paginate(
                5,
                ['*'],
                'page_disciplinas'
            );

        /*
        |--------------------------------------------------------------------------
        | Grupos PAP
        |--------------------------------------------------------------------------
        */

        $grupos = $turma->gruposPap()
            ->select(
                'id',
                'turma_id',
                'nome_grupo',
                'tema_grupo',
                'status',
                'nota_final'
            )
            ->paginate(
                5,
                ['*'],
                'page_grupos'
            );

        /*
        |--------------------------------------------------------------------------
        | Pauta
        |--------------------------------------------------------------------------
        */

        $pautaRecurso = $this->pautaService
            ->gerarPauta($turma, 4, 5);
        $podeLancarRecurso = $user->hasAnyRole(['Director', 'Subdirector'])
            || collect($pautaRecurso['alunos'] ?? [])
                ->contains(fn ($aluno) => is_null($aluno['nota_recurso'] ?? null));

        /*
        |--------------------------------------------------------------------------
        | Retornar página
        |--------------------------------------------------------------------------
        */

        return Inertia::render(
            'tenant/colegio/cursos-tutelados/classes/turnos/turmas/show',
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

                    'curso' => [
                        'id' => $cursoTutelado
                            ->instituicaoCurso
                            ->curso
                            ->id,

                        'nome' => $cursoTutelado
                            ->instituicaoCurso
                            ->curso
                            ->nome,
                    ],

                    /*
                    | Colégio onde o curso é ministrado
                    */

                    'colegio' => [
                        'id' => $cursoTutelado
                            ->instituicaoCurso
                            ->instituicao
                            ->id,

                        'nome' => $cursoTutelado
                            ->instituicaoCurso
                            ->instituicao
                            ->nome,
                    ],

                    /*
                    | Instituto que tutela
                    */

                    'instituicao_tutora' => [
                        'id' => $cursoTutelado
                            ->instituicaoTutora
                            ->id,

                        'nome' => $cursoTutelado
                            ->instituicaoTutora
                            ->nome,
                    ],
                ],

                /*
                |--------------------------------------------------------------------------
                | Curso Classe
                |--------------------------------------------------------------------------
                */

                'cursoClasse' => [
                    'id' => $cursoClasse->id,

                    'classe' => [
                        'id' => $cursoClasse
                            ->classe
                            ->id,

                        'nome' => $cursoClasse
                            ->classe
                            ->nome,
                    ],
                ],

                /*
                |--------------------------------------------------------------------------
                | Turno
                |--------------------------------------------------------------------------
                */

                'cursoClasseTurno' => [
                    'id' => $cursoClasseTurno->id,

                    'turno' => [
                        'id' => $cursoClasseTurno
                            ->turno
                            ->id,

                        'nome' => $cursoClasseTurno
                            ->turno
                            ->nome,
                    ],
                ],

                /*
                |--------------------------------------------------------------------------
                | Turma
                |--------------------------------------------------------------------------
                */

                'turma' => new TurmaShowResource($turma),

                /*
                |--------------------------------------------------------------------------
                | Ano lectivo
                |--------------------------------------------------------------------------
                */

                'anoLectivoId' => $anoLectivoId,

                'anosLectivos' => AnoLectivo::all(),

                /*
                |--------------------------------------------------------------------------
                | Permissões
                |--------------------------------------------------------------------------
                */

                'can' => [
                    'alunos' => [
                        'create' => $user->can(
                            'create',
                            Aluno::class
                        ),
                    ],

                    'disciplinas' => [
                        'create' => $user->can(
                            'create',
                            ClasseTurnoDisciplina::class
                        ),
                    ],

                    'grupos' => [
                        'create' => $user->can(
                            'create',
                            GrupoPap::class
                        ),
                    ],
                ],

                /*
                |--------------------------------------------------------------------------
                | Dados
                |--------------------------------------------------------------------------
                */

                'alunos' => AlunoTurmaResource::collection(
                    $alunos
                ),

                'disciplinas' => ClasseTurnoDisciplinaResource::collection(
                    $disciplinas
                ),

                'pautaRecurso' => $pautaRecurso,
                'pode_lancar_recurso' => $podeLancarRecurso,

                'grupos' => GrupoPapIndexResource::collection(
                    $grupos
                ),
            ]
        );
    }
}
