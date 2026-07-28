<?php

namespace App\Http\Controllers\Colegios;

use App\Http\Controllers\Controller;
use App\Models\AnoLectivo;
use App\Models\ClasseTurnoDisciplina;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use App\Models\Turma;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CursoClasseController extends Controller
{
    /**
     * Display the specified resource (Show page via Inertia).
     */
    public function show(
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse
    ) {
        // Garantir que o CursoClasse pertence ao Curso Tutelado
        abort_unless(
            $cursoClasse->curso_tutelado_id === $cursoTutelado->id,
            404
        );

        $cursoClasse->load([
            'classe:id,nome',
            'turnos.turno:id,nome',
        ]);

        $anoLectivoId = request('ano_lectivo_id')
            ?? AnoLectivo::where('activo', 1)->first()?->id;

        // Turno selecionado
        $turnoId = request('turno');

        $turnoActual = $cursoClasse->turnos
            ->firstWhere('id', $turnoId)
            ?? $cursoClasse->turnos->first();

        $turnoId = $turnoActual?->id;

        // Buscar apenas as turmas do turno
        $turmas = $turnoActual
            ? $turnoActual->turmas()
                ->where('ano_lectivo_id', $anoLectivoId)
                ->withCount('alunosActivos')
                ->orderBy('nome')
                ->paginate(5, ['*'], 'page_turmas')
            : $this->emptyPaginator('page_turmas');

        return Inertia::render('colegio/cursos-tutelados/classes/show', [
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
            ],

            'colegio' => [
                'id' => $colegio,
            ],

            'cursoTutelado' => [
                'id' => $cursoTutelado->id,
                'curso' => [
                    'id' => $cursoTutelado->instituicaoCurso->curso->id,
                    'nome' => $cursoTutelado->instituicaoCurso->curso->nome,
                ],
                'instituicao' => [
                    'id' => $cursoTutelado->instituicaoCurso->instituicao->id,
                    'nome' => $cursoTutelado->instituicaoCurso->instituicao->nome,
                ],
                'instituicao_tutora' => [
                    'id' => $cursoTutelado->instituicaoTutora->id,
                    'nome' => $cursoTutelado->instituicaoTutora->nome,
                ],
            ],

            'cursoClasse' => [
                'id' => $cursoClasse->id,

                'classe' => [
                    'id' => $cursoClasse->classe->id,
                    'nome' => $cursoClasse->classe->nome,
                ],

                'turnos' => $cursoClasse->turnos
                    ->map(fn($t) => [
                        'id' => $t->id,
                        'nome' => $t->turno->nome,
                    ])
                    ->values(),

                'turnoId' => $turnoId,

                'turmas' => $turmas->through(
                    fn(Turma $turma) => [
                        'id' => $turma->id,
                        'nome' => $turma->nome,
                        'alunos_activos_count' => $turma->alunos_activos_count,
                        'can' => [
                            'view' => Auth::user()->can('view', $turma),
                            'edit' => Auth::user()->can('update', $turma),
                        ],
                    ]
                ),
            ],

            'anosLectivos' => AnoLectivo::all(),
            'anoLectivoActual' => $anoLectivoId,
        ]);
    }
}
