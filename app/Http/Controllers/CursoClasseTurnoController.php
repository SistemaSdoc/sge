<?php

namespace App\Http\Controllers;

use App\Http\Resources\CursoClasseTurno\CursoClasseTurnoResource;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use App\Models\Turno;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CursoClasseTurnoController extends Controller
{
    public function index(Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        $cursoTutelado->load([
            'cursoClasses.classe:id,nome',
            'cursoClasses.turnos.turno:id,nome',
        ]);

        if (! request()->header('X-Inertia') && (request()->wantsJson() || request()->ajax())) {
            return CursoClasseTurnoResource::collection($cursoTutelado->cursoClasses);
        }

        $turnos = Turno::select('id', 'nome')->orderBy('nome')->get();

        return Inertia::render('cursos-tutelados/classes/create', [
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
            ],
            'cursoTutelado' => [
                'id' => $cursoTutelado->id,
                'nome' => $cursoTutelado->instituicaoCurso->curso->nome,
            ],
            'classesTurnos' => $cursoTutelado->cursoClasses->map(function ($cc) {
                return [
                    'id' => $cc->id,
                    'classe' => [
                        'id' => $cc->classe->id,
                        'nome' => $cc->classe->nome,
                    ],
                    'turnos' => $cc->turnos->map(function ($t) {
                        return [
                            'turno' => [
                                'id' => $t->turno->id,
                                'nome' => $t->turno->nome,
                            ],
                        ];
                    })->toArray(),
                ];
            })->toArray(),
            'turnos' => $turnos,
        ]);
    }

    // Retorna turmas e disciplinas paginadas de um turno específico
    public function turnoData(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Request $request
    ) {
        abort_if($cursoClasseTurno->curso_classe_id !== $cursoClasse->id, 404);

        $turmas = $cursoClasseTurno->turmas()
            ->withCount('alunos')
            ->orderBy('nome')
            ->paginate(5, ['*'], 'turmas_page');

        $disciplinas = $cursoClasseTurno->classeTurnoDisciplinas()
            ->with('disciplina:id,nome,sigla,componente')
            ->paginate(5, ['*'], 'disciplinas_page');

        return response()->json([
            'turmas' => [
                'data' => $turmas->map(fn ($t) => [
                    'id' => $t->id,
                    'nome' => $t->nome,
                    'alunos_count' => $t->alunos_count,
                ]),
                'current_page' => $turmas->currentPage(),
                'last_page' => $turmas->lastPage(),
                'total' => $turmas->total(),
            ],
            'disciplinas' => [
                'data' => $disciplinas->map(fn ($ctd) => [
                    'id' => $ctd->disciplina->id,
                    'nome' => $ctd->disciplina->nome,
                    'sigla' => $ctd->disciplina->sigla,
                    'componente' => $ctd->disciplina->componente,
                ]),
                'current_page' => $disciplinas->currentPage(),
                'last_page' => $disciplinas->lastPage(),
                'total' => $disciplinas->total(),
            ],
        ]);
    }

    public function create(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse
    ) {
        abort_if($cursoClasse->curso_tutelado_id !== $cursoTutelado->id, 404);

        return Inertia::render('cursos-tutelados/classes/create', [
            'instituicao' => $instituicao,
            'cursoTutelado' => $cursoTutelado,
            'cursoClasse' => $cursoClasse,
            'turnos' => Turno::all(),
        ]);

    }

    public function store(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse
    ) {
        abort_if($cursoClasse->curso_tutelado_id !== $cursoTutelado->id, 404);

        $validated = $request->validate([
            'turnos' => ['required', 'array'],
            'turnos.*' => ['string', 'exists:turnos,id'],
        ]);

        foreach ($validated['turnos'] as $turnoId) {
            $exists = $cursoClasse->turnos()
                ->where('turno_id', $turnoId)
                ->exists();

            if (! $exists) {
                $cursoClasse->turnos()->create([
                    'turno_id' => $turnoId,
                ]);
            }
        }

        return to_route('cursos-tutelados.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
        ])->with('toast', [
            'type' => 'success',
            'message' => 'Turnos adicionados com sucesso!',
        ]);
    }

    // public function store(Request $request, Instituicao $instituicao, CursoTutelado $cursoTutelado, CursoClasse $cursoClasse)
    // {
    //     abort_if($cursoClasse->curso_tutelado_id !== $cursoTutelado->id, 404);

    //     $validated = $request->validate([
    //         'turnos' => ['required', 'array'],
    //         'turnos.*' => ['string', 'exists:turnos,id'],
    //     ]);

    //     // verifica se algum turno a remover tem turmas associadas
    //     $turnosActuais = $cursoClasse->turnos->pluck('turno_id');
    //     $turnosNovos = collect($validated['turnos']);
    //     $turnosARemover = $turnosActuais->diff($turnosNovos);

    //     if ($turnosARemover->isNotEmpty()) {
    //         $temTurmas = $cursoClasse->turnos()
    //             ->whereIn('turno_id', $turnosARemover)
    //             ->whereHas('turmas')
    //             ->exists();

    //         if ($temTurmas) {
    //             return response()->json([
    //                 'message' => 'Não é possível remover um turno que tem turmas associadas.',
    //             ], 422);
    //         }
    //     }

    //     $cursoClasse->turnos()->whereNotIn('turno_id', $validated['turnos'])->delete();

    //     foreach ($validated['turnos'] as $turnoId) {
    //         $cursoClasse->turnos()->firstOrCreate(['turno_id' => $turnoId]);
    //     }

    //     return to_route('cursos-tutelados.show', [
    //         'instituicao' => $instituicao->id,
    //         'cursoTutelado' => $cursoTutelado->id,
    //     ])->with('toast', [
    //         'type' => 'success',
    //         'message' => 'Turnos atualizados com sucesso!',
    //     ]);
    // }
}
