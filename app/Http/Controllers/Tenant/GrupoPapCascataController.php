<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Tenant\TurmaResource;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Turma;
use App\Services\Tenant\GrupoPapViewService;
use Illuminate\Http\Request;
use App\Models\Tenant\Instituicao;

class GrupoPapCascataController extends Controller
{
    public function classes(Request $request, Instituicao $instituicao)
    {
        $classes = CursoClasse::query()
            ->where('curso_tutelado_id', $request->input('curso_tutelado_id'))
            ->whereHas('classe', fn($q) => $q->where('nome', 'LIKE', '13%')) // ← filtro aqui
            ->with('classe:id,nome')
            ->orderBy('id')
            ->get()
            ->map(fn($cursoClasse) => [
                'id' => $cursoClasse->id,
                'nome' => $cursoClasse->classe?->nome ?? 'Classe',
            ])
            ->values();

        return response()->json($classes);
    }
    public function turnos(Request $request, Instituicao $instituicao)
    {
        $turnos = CursoClasseTurno::query()
            ->where('curso_classe_id', $request->input('curso_classe_id'))
            ->with('turno:id,nome')
            ->get()
            ->map(fn($cct) => ['id' => $cct->id, 'nome' => $cct->turno->nome])
            ->values();

        return response()->json($turnos);
    }

    public function turmas(Request $request, Instituicao $instituicao)
    {
        $turmas = Turma::query()
            ->where('curso_classe_turno_id', $request->input('curso_classe_turno_id'))
            ->orderBy('nome')
            ->get(['id', 'nome']);

        return response()->json($turmas);
    }

    public function formOptions(Request $request, Instituicao $instituicao)
    {
        $cursoTutelado = CursoTutelado::find($request->input('curso_tutelado_id'));
        $turma = Turma::find($request->input('turma_id'));

        if (!$cursoTutelado || !$turma) {
            return response()->json(['professores' => [], 'alunos' => []]);
        }

        $classeNome = $turma->cursoClasseTurno?->cursoClasse?->classe?->nome ?? '';

        if (!str_contains(strtolower($classeNome), '13')) {
            return response()->json(['professores' => [], 'alunos' => []]);
        }

        $options = app(GrupoPapViewService::class)->createOptions($cursoTutelado, $turma);

        return response()->json([
            'professores' => $options['professores']->map(fn($p) => [
                'id' => $p->id,
                'nome' => $p->user?->nome ?? 'Sem nome',
            ])->values(),
            'alunos' => $options['alunos'],
        ]);
    }
}