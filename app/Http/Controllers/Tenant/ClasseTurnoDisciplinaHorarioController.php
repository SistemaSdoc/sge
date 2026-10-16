<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreClasseTurnoDisciplinaHorarioRequest;
use App\Models\Tenant\ClasseTurnoDisciplina;
use App\Models\Tenant\ClasseTurnoDisciplinaHorario;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ClasseTurnoDisciplinaHorarioController extends Controller
{
    /**
     * Página para definir (ou editar) os horários de uma disciplina numa turma
     */
    public function create(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        ClasseTurnoDisciplina $classeTurnoDisciplina
    ) {
        $podeGerir = Gate::allows('create', new ClasseTurnoDisciplinaHorario);
        $classeTurnoDisciplina->loadMissing('disciplina');

        $horarios = $classeTurnoDisciplina->horarios()
            ->where('turma_id', $turma->id)
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get()
            ->map(fn ($h) => [
                'dia_semana' => (int) $h->dia_semana,
                'hora_inicio' => $this->formatarHora($h->hora_inicio),
                'hora_fim' => $this->formatarHora($h->hora_fim),
            ])
            ->values();

        return Inertia::render('tenant/horarios/create', [
            'params' => [
                'instituicao' => $instituicao->id,
                'cursoTutelado' => $cursoTutelado->id,
                'cursoClasse' => $cursoClasse->id,
                'cursoClasseTurno' => $cursoClasseTurno->id,
                'turma' => $turma->id,
                'classeTurnoDisciplina' => $classeTurnoDisciplina->id,
            ],
            'turma' => ['id' => $turma->id, 'nome' => $turma->nome],
            'disciplina' => [
                'nome' => $classeTurnoDisciplina->disciplina->nome,
                'sigla' => $classeTurnoDisciplina->disciplina->sigla,
            ],
            'horarios' => $horarios,
            'anoLectivoId' => $request->input('ano_lectivo_id'),
            'readOnly' => ! $podeGerir,
        ]);
    }

    /**
     * Salvar horários de uma disciplina dentro de uma turma
     */
    public function store(
        StoreClasseTurnoDisciplinaHorarioRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        ClasseTurnoDisciplina $classeTurnoDisciplina
    ) {
        Gate::authorize('create', new ClasseTurnoDisciplinaHorario);

        // Remover horários antigos
        $classeTurnoDisciplina->horarios()->where('turma_id', $turma->id)->delete();

        // Criar novos horários
        $horarios = collect($request->validated()['horarios'])
            ->map(fn ($horario) => array_merge($horario, [
                'id' => (string) Str::uuid7(),
                'classe_turno_disciplina_id' => $classeTurnoDisciplina->id,
                'turma_id' => $turma->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]))
            ->toArray();

        ClasseTurnoDisciplinaHorario::insert($horarios);

        // Preservar filtro de ano_lectivo
        $anoLectivoParam = filled($request->input('ano_lectivo_id'))
            ? ['ano_lectivo_id' => $request->input('ano_lectivo_id')]
            : [];

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
        ] + $anoLectivoParam)->with('success', 'Horários definidos com sucesso.');
    }

    private function formatarHora($valor): string
    {
        return $valor instanceof \DateTimeInterface
            ? $valor->format('H:i')
            : substr((string) $valor, 0, 5);
    }
}
