<?php

namespace App\Http\Controllers\Tenant\Colegios;

use App\Http\Controllers\Controller;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
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
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

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

        return Inertia::render('tenant/colegio/cursos-tutelados/classes/show', [
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
                    ->map(fn ($t) => [
                        'id' => $t->id,
                        'nome' => $t->turno->nome,
                    ])
                    ->values(),

                'turnoId' => $turnoId,

                'turmas' => $turmas->through(
                    fn (Turma $turma) => [
                        'id' => $turma->id,
                        'nome' => $turma->nome,
                        'alunos_activos_count' => $turma->alunos_activos_count,
                        'can' => [
                            'view' => $user->can('view', $turma),
                            'edit' => $user->can('update', $turma),
                        ],
                    ]
                ),
            ],

            'anosLectivos' => AnoLectivo::all(),
            'anoLectivoActual' => $anoLectivoId,
        ]);
    }
}
