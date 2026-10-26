<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Central\Disciplina as DisciplinaCentral;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\Aula;
use App\Models\Tenant\ClasseTurnoDisciplinaHorario;
use App\Models\Tenant\Professor;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AulaController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Aula::class, 'aula', [
            'except' => ['show'],
        ]);
    }
    // ============================================================
    // INDEX — ramifica por perfil
    // ============================================================

    public function index(Request $request)
    {
        $user = $request->user('tenant');

        if ($user->hasAnyRole(['Director', 'Subdirector', 'Secretaria'])) {
            return $this->indexParaDirecao($request, $user);
        }

        if ($user->hasRole('Aluno')) {
            return $this->indexParaAluno($request, $user);
        }

        if ($user->hasRole('Professor')) {
            return $this->indexParaProfessor($request, $user);
        }

        return $this->indexParaDirecao($request, $user);
    }

    // ────────────────────────────────────────────────────────────
    // Professor: agenda cruzada (slots × registos)
    // ────────────────────────────────────────────────────────────

    private function indexParaProfessor(Request $request, User $user)
    {
        $professor = $user->professor;
        abort_unless($professor, 403, 'Perfil de professor não encontrado.');

        // Semana em curso: segunda a domingo
        $de  = $request->date('de')  ?? Carbon::now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $ate = $request->date('ate') ?? Carbon::now()->endOfWeek(Carbon::SUNDAY)->startOfDay();

        $slots = $this->slotsDoProfessor($professor);

        $registos = Aula::query()
            ->doProfessor($professor->id)
            ->entreDatas($de, $ate)
            ->with(['professorOriginal.user'])
            ->get()
            ->keyBy(
                fn(Aula $a) => ($a->classe_turno_disciplina_horario_id ?? 'extra') . '|' . $a->data->format('Y-m-d')
            );

        $agenda = [];
        $cursor = $de->copy();

        while ($cursor->lte($ate)) {
            $diaSemana = (int) $cursor->dayOfWeek;

            $slotsDoDia = $slots
                ->filter(fn($s) => (int) $s['dia_semana'] === $diaSemana)
                ->sortBy('hora_inicio')
                ->values();

            if ($slotsDoDia->isEmpty()) {
                $cursor->addDay();
                continue;
            }

            $aulasDoDia = [];

            foreach ($slotsDoDia as $slot) {
                $chave    = $slot['id'] . '|' . $cursor->format('Y-m-d');
                $registro = $registos->get($chave);

                $aulasDoDia[] = [
                    'slot'   => $slot,
                    'aula'   => $registro ? $this->formatarAula($registro, $professor->id) : null,
                    'estado' => $registro?->estado ?? 'sem_registo',
                ];
            }

            $agenda[] = [
                'data'        => $cursor->format('Y-m-d'),
                'data_humana' => $cursor->translatedFormat('l, d \d\e F'),
                'dia_semana'  => $diaSemana,
                'is_hoje'     => $cursor->isToday(),
                'is_passado'  => $cursor->isPast(),
                'aulas'       => $aulasDoDia,
            ];

            $cursor->addDay();
        }

        $extras = Aula::query()
            ->doProfessor($professor->id)
            ->entreDatas($de, $ate)
            ->whereNull('classe_turno_disciplina_horario_id')
            ->with(['turma', 'disciplina'])
            ->orderBy('data')
            ->get()
            ->map(fn(Aula $a) => $this->formatarAula($a, $professor->id))
            ->values();

        return Inertia::render('tenant/professores/aulas/index', [
            'agenda'  => $agenda,
            'extras'  => $extras,
            'filtros' => [
                'de'  => $de->format('Y-m-d'),
                'ate' => $ate->format('Y-m-d'),
            ],
        ]);
    }

    // ────────────────────────────────────────────────────────────
    // Aluno: agenda cruzada (slots das suas turmas + publicados)
    // ────────────────────────────────────────────────────────────

    private function indexParaAluno(Request $request, User $user)
    {
        $aluno = $user->aluno;
        abort_unless($aluno, 403, 'Perfil de aluno não encontrado.');

        $de  = $request->date('de')  ?? Carbon::today()->startOfWeek(Carbon::MONDAY);
        $ate = $request->date('ate') ?? Carbon::today()->endOfWeek(Carbon::SUNDAY);
        $turmaIds = $this->turmasDoAluno($aluno->id);

        $slots = ClasseTurnoDisciplinaHorario::query()
            ->whereIn('turma_id', $turmaIds)
            ->with([
                'turma:id,nome',
                'classeTurnoDisciplina.disciplina:id,nome,sigla',
            ])
            ->get();

        $registos = Aula::query()
            ->whereIn('turma_id', $turmaIds)
            ->publicado()
            ->entreDatas($de, $ate)
            ->get()
            ->keyBy(
                fn(Aula $a) => ($a->classe_turno_disciplina_horario_id ?? 'extra') . '|' . $a->data->format('Y-m-d')
            );

        $agenda = [];
        $cursor = $de->copy();

        while ($cursor->lte($ate)) {
            $diaSemana = (int) $cursor->dayOfWeek;

            $slotsDoDia = $slots
                ->filter(fn($s) => (int) $s->dia_semana === $diaSemana)
                ->sortBy('hora_inicio')
                ->values();

            if ($slotsDoDia->isEmpty()) {
                $cursor->addDay();
                continue;
            }

            $aulasDoDia = [];

            foreach ($slotsDoDia as $slot) {
                $chave    = $slot->id . '|' . $cursor->format('Y-m-d');
                $registro = $registos->get($chave);

                $aulasDoDia[] = [
                    'slot'   => $this->formatarSlot($slot),
                    'aula'   => $registro ? $this->formatarAula($registro, null) : null,
                    'estado' => $registro?->estado ?? 'sem_registo',
                ];
            }

            $agenda[] = [
                'data'        => $cursor->format('Y-m-d'),
                'data_humana' => $cursor->translatedFormat('l, d \d\e F'),
                'dia_semana'  => $diaSemana,
                'is_hoje'     => $cursor->isToday(),
                'is_passado'  => $cursor->isPast(),
                'aulas'       => $aulasDoDia,
            ];

            $cursor->addDay();
        }

        return Inertia::render('tenant/alunos/aulas/index', [
            'agenda'  => $agenda,
            'filtros' => [
                'de'  => $de->format('Y-m-d'),
                'ate' => $ate->format('Y-m-d'),
            ],
        ]);
    }

    // ────────────────────────────────────────────────────────────
    // Direção/Coordenação: tudo com filtros
    // ────────────────────────────────────────────────────────────

    private function indexParaDirecao(Request $request, User $user)
    {
        $de  = $request->date('de')  ?? Carbon::today()->subDays(7);
        $ate = $request->date('ate') ?? Carbon::today()->addDays(7);

        $query = Aula::query()
            ->entreDatas($de, $ate)
            ->with(['turma', 'disciplina', 'professor.user']);

        if ($disciplinaId = $request->input('disciplina_id')) {
            $query->where('disciplina_id', $disciplinaId);
        }

        if ($professorId = $request->input('professor_id')) {
            $query->doProfessor($professorId);
        }

        if ($estado = $request->input('estado')) {
            $query->where('estado', $estado);
        }

        $aulas = $query
            ->orderBy('data', 'desc')
            ->orderBy('hora_inicio')
            ->paginate(50)
            ->through(fn(Aula $a) => $this->formatarAula($a, null));

        $professores = DB::table('professores')
            ->join('users', 'professores.user_id', '=', 'users.id')
            ->select('professores.id', 'users.email as nome')
            ->orderBy('users.email')
            ->get()
            ->map(fn($p) => ['id' => $p->id, 'nome' => $p->nome])
            ->toArray();

        return Inertia::render('tenant/aulas/index', [
            'aulas'       => $aulas,
            'filtros'     => $request->only('de', 'ate', 'disciplina_id', 'professor_id', 'estado'),
            'professores' => $professores,
            'disciplinas' => $this->disciplinasDisponiveis(),
        ]);
    }

    // ============================================================
    // CREATE
    // ============================================================

    public function create(Request $request)
    {
        $user      = $request->user('tenant');
        $professor = $user->professor;

        $slot          = null;
        $aulaExistente = null;
        $base          = null;

        if ($slotId = $request->input('slot_id')) {
            $slot = ClasseTurnoDisciplinaHorario::with([
                'classeTurnoDisciplina.disciplina',
                'turma',
            ])->findOrFail($slotId);

            if ($data = $request->input('data')) {
                $aulaExistente = Aula::where('classe_turno_disciplina_horario_id', $slotId)
                    ->where('data', $data)
                    ->first();
            }
        }

        if ($baseId = $request->input('base_id')) {
            $base = Aula::with(['turma', 'disciplina'])->find($baseId);
        }

        return Inertia::render('tenant/professores/aulas/form', [
            'slot'           => $slot ? $this->formatarSlot($slot) : null,
            'aula_existente' => $aulaExistente ? $this->formatarAula($aulaExistente, $professor->id) : null,
            'base'           => $base ? $this->formatarAula($base, $professor->id) : null,
            'data_sugerida'  => $request->input('data'),
            'turmas'         => $this->turmasDoProfessor($professor),
            'disciplinas'    => $this->disciplinasDisponiveis(),
        ]);
    }

    // ============================================================
    // STORE
    // ============================================================

    public function store(Request $request)
    {
        $user      = $request->user('tenant');
        $professor = $user->professor;

        $dados = $request->validate([
            'classe_turno_disciplina_horario_id' => ['nullable', 'uuid'],
            'data'          => ['required', 'date'],
            'turma_id'      => ['required_without:classe_turno_disciplina_horario_id', 'uuid'],
            'disciplina_id' => ['required_without:classe_turno_disciplina_horario_id', 'uuid'],
            'hora_inicio'   => ['nullable', 'date_format:H:i'],
            'hora_fim'      => ['nullable', 'date_format:H:i', 'after:hora_inicio'],
            'tipo'          => ['required', 'in:normal,substituicao,extra,reposicao'],
            'titulo'        => ['nullable', 'string', 'max:200'],
            'conteudo_previsto' => ['nullable', 'string', 'max:2000'],
            'sumario'       => ['nullable', 'string', 'max:2000'],
            'tpc'           => ['nullable', 'string', 'max:1000'],
            'observacoes'   => ['nullable', 'string', 'max:1000'],
            'professor_original_id' => ['nullable', 'uuid'],
        ]);

        if ($slotId = $dados['classe_turno_disciplina_horario_id'] ?? null) {
            $slot = ClasseTurnoDisciplinaHorario::with('classeTurnoDisciplina')->findOrFail($slotId);

            $dados['turma_id']      = $slot->turma_id;
            $dados['disciplina_id'] = $slot->classeTurnoDisciplina->disciplina_id;
            $dados['hora_inicio']   = $slot->hora_inicio;
            $dados['hora_fim']      = $slot->hora_fim;

            $existente = Aula::where('classe_turno_disciplina_horario_id', $slotId)
                ->where('data', $dados['data'])
                ->first();

            if ($existente) {
                return redirect()
                    ->route('tenant.dashboard.professor.aulas.edit', $existente)
                    ->with('info', 'Já existia registo para esta aula. Podes continuar a edição.');
            }
        }

        $dados['professor_id'] = $professor->id;

        if ($dados['tipo'] !== 'substituicao') {
            $dados['professor_original_id'] = null;
        }

        $aula = Aula::create($dados);

        return redirect()
            ->route('tenant.dashboard.professor.aulas.edit', $aula)
            ->with('success', 'Rascunho criado. Preenche e publica quando estiver pronto.');
    }

    // ============================================================
    // EDIT
    // ============================================================

    public function edit(Request $request, Aula $aula)
    {
        $user      = $request->user('tenant');
        $professor = $user->professor;

        $aula->load(['turma', 'disciplina', 'professorOriginal.user']);

        $slotFormatado = null;
        if ($aula->horario) {
            $slotFormatado = $this->formatarSlot(
                $aula->horario->load(['classeTurnoDisciplina.disciplina', 'turma'])
            );
        }

        return Inertia::render('tenant/professores/aulas/form', [
            'aula'           => $this->formatarAula($aula, $professor->id),
            'slot'           => $slotFormatado,
            'aula_existente' => $this->formatarAula($aula, $professor->id),
            'base'           => null,
            'data_sugerida'  => $aula->data->format('Y-m-d'),
            'turmas'         => $this->turmasDoProfessor($professor),
            'disciplinas'    => $this->disciplinasDisponiveis(),
        ]);
    }

    // ============================================================
    // UPDATE
    // ============================================================

    public function update(Request $request, Aula $aula)
    {
        $dados = $request->validate([
            'titulo'            => ['nullable', 'string', 'max:200'],
            'conteudo_previsto' => ['nullable', 'string', 'max:2000'],
            'sumario'           => ['nullable', 'string', 'max:2000'],
            'tpc'               => ['nullable', 'string', 'max:1000'],
            'observacoes'       => ['nullable', 'string', 'max:1000'],
            'hora_inicio'       => ['nullable', 'date_format:H:i'],
            'hora_fim'          => ['nullable', 'date_format:H:i', 'after:hora_inicio'],
        ]);

        $aula->update($dados);

        return back()->with('success', 'Aula atualizada.');
    }

    // ============================================================
    // PUBLICAR
    // ============================================================

    public function publicar(Request $request, Aula $aula)
    {
        $this->authorize('publicar', $aula);

        if ($aula->estado === 'publicado') {
            return back()->with('info', 'Esta aula já está publicada.');
        }

        if ($aula->estado === 'cancelado') {
            return back()->with('error', 'Não é possível publicar uma aula cancelada.');
        }

        $aula->update([
            'estado'       => 'publicado',
            'publicado_em' => now(),
        ]);

        return back()->with('success', 'Aula publicada. Os alunos podem consultar.');
    }

    // ============================================================
    // CANCELAR
    // ============================================================

    public function cancelar(Request $request, Aula $aula)
    {
        $this->authorize('cancelar', $aula);

        $dados = $request->validate([
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ]);

        $aula->update([
            'estado'      => 'cancelado',
            'observacoes' => $dados['observacoes'] ?? $aula->observacoes,
        ]);

        return back()->with('success', 'Aula cancelada.');
    }

    // ============================================================
    // SHOW
    // ============================================================

    public function show(Request $request, Aula $aula)
    {
        $user = $request->user('tenant');

        // ── ALUNO: mensagens sensíveis ──
        if ($user->hasRole('Aluno')) {
            $turmasAluno = $user->aluno?->turmas()->pluck('turmas.id')->toArray() ?? [];

            // Caso A: aula de outra turma → 404 (não revelar que existe)
            if (! in_array($aula->turma_id, $turmasAluno)) {
                abort(404, 'Aula não encontrada.');
            }

            // Caso B: cancelada → mensagem amigável
            if ($aula->estado === 'cancelado') {
                return redirect()
                    ->route('tenant.dashboard.aulas.index')
                    ->with('info', 'Esta aula foi cancelada pelo professor.');
            }

            // Caso C: rascunho → mensagem amigável
            if ($aula->estado === 'rascunho') {
                return redirect()
                    ->route('tenant.dashboard.aulas.index')
                    ->with('info', 'Esta aula ainda não está disponível. O professor irá publicá-la em breve.');
            }
        }

        // ── PROFESSOR / DIREÇÃO: policy normal ──
        if (! $user->hasRole('Aluno')) {
            Gate::authorize('view', $aula);
        }

        // ── Resto do método original ──
        $aula->load([
            'turma',
            'disciplina',
            'professor.user',
            'professorOriginal.user',
            'horario.classeTurnoDisciplina.disciplina',
        ]);

        $professorId = $user->professor?->id;

        $podeEditar = $user->hasRole('Professor')
            && $professorId !== null
            && $professorId === $aula->professor_id
            && $aula->esta_editavel;

        $urlVoltar = $user->hasRole('Professor')
            ? route('tenant.dashboard.professor.aulas.index')
            : route('tenant.dashboard.aulas.index');

        return Inertia::render('tenant/aulas/show', [
            'aula'       => $this->formatarAula($aula, $professorId),
            'podeEditar' => $podeEditar,
            'url_voltar' => $urlVoltar,
        ]);
    }
    // ============================================================
    // DESTROY
    // ============================================================

    public function destroy(Request $request, Aula $aula)
    {
        $this->authorize('delete', $aula);

        $aula->delete();

        return redirect()
            ->route('tenant.dashboard.professor.aulas.index')
            ->with('success', 'Rascunho removido.');
    }

    // ============================================================
    // HELPERS
    // ============================================================

    public function restaurarEstado(User $user, Aula $aula): bool
    {
        return $this->donoDaAula($user, $aula) && $aula->estado === 'cancelado';
    }

    public function restaurar(Request $request, Aula $aula)
    {
        $this->authorize('restaurarEstado', $aula);

        $aula->update([
            'estado'      => $aula->publicado_em ? 'publicado' : 'rascunho',
            'publicado_em' => $aula->publicado_em,
        ]);

        return back()->with('success', 'Aula reativada.');
    }

    private function slotsDoProfessor(Professor $professor)
    {
        return ClasseTurnoDisciplinaHorario::query()
            ->whereHas('classeTurnoDisciplina.turmaDisciplinaProfessores', function ($q) use ($professor) {
                $q->where('professor_id', $professor->id);
            })
            ->with([
                'turma:id,nome',
                'classeTurnoDisciplina.disciplina:id,nome,sigla',
            ])
            ->get()
            ->map(fn($h) => $this->formatarSlot($h))
            ->values();
    }

    private function formatarSlot(ClasseTurnoDisciplinaHorario $slot): array
    {
        return [
            'id'               => $slot->id,
            'dia_semana'       => (int) $slot->dia_semana,
            'hora_inicio'      => $this->formatarHora($slot->hora_inicio),
            'hora_fim'         => $this->formatarHora($slot->hora_fim),
            'turma_id'         => $slot->turma_id,
            'turma_nome'       => $slot->turma?->nome,
            'disciplina_id'    => $slot->classeTurnoDisciplina?->disciplina_id,
            'disciplina_nome'  => $slot->classeTurnoDisciplina?->disciplina?->nome,
            'disciplina_sigla' => $slot->classeTurnoDisciplina?->disciplina?->sigla,
        ];
    }

    private function formatarAula(Aula $a, ?string $professorId): array
    {
        return [
            'id'              => $a->id,
            'data'            => $a->data?->format('Y-m-d'),
            'data_humana'     => $a->data?->translatedFormat('l, d \d\e F'),
            'hora_inicio'     => $this->formatarHora($a->hora_inicio),
            'hora_fim'        => $this->formatarHora($a->hora_fim),
            'turma_id'        => $a->turma_id,
            'turma_nome'      => $a->turma?->nome,
            'disciplina_id'   => $a->disciplina_id,
            'disciplina_nome' => $a->disciplina?->nome,
            'professor_nome'  => $a->professor?->user?->email,
            'professor_original_nome' => $a->professorOriginal?->user?->email,
            'tipo'            => $a->tipo,
            'tipo_label'      => $a->tipo_label,
            'titulo'          => $a->titulo,
            'conteudo_previsto' => $a->conteudo_previsto,
            'sumario'         => $a->sumario,
            'tpc'             => $a->tpc,
            'observacoes'     => ($professorId !== null && $professorId === $a->professor_id)
                ? $a->observacoes
                : null,
            'estado'          => $a->estado,
            'estado_label'    => $a->estado_label,
            'publicado_em'    => $a->publicado_em?->format('d/m/Y H:i'),
            'esta_editavel'   => $a->esta_editavel,
            'horas_restantes_edicao' => $a->horas_restantes_edicao,
            'tem_sumario'     => $a->tem_sumario,

            'urls' => [
                'ver'      => route('tenant.dashboard.aulas.show', $a),
                'editar'   => route('tenant.dashboard.professor.aulas.edit', $a),
                'publicar' => route('tenant.dashboard.professor.aulas.publicar', $a),
                'cancelar' => route('tenant.dashboard.professor.aulas.cancelar', $a),
                'apagar'   => route('tenant.dashboard.professor.aulas.destroy', $a),
            ],
        ];
    }

    private function turmasDoProfessor(Professor $professor): array
    {
        return DB::table('turma_disciplina_professor')
            ->join('classe_turno_disciplina', 'turma_disciplina_professor.classe_turno_disciplina_id', '=', 'classe_turno_disciplina.id')
            ->join('turmas', 'classe_turno_disciplina.curso_classe_turno_id', '=', 'turmas.curso_classe_turno_id')
            ->where('turma_disciplina_professor.professor_id', $professor->id)
            ->select('turmas.id', 'turmas.nome')
            ->distinct()
            ->orderBy('turmas.nome')
            ->get()
            ->map(fn($t) => ['id' => $t->id, 'nome' => $t->nome])
            ->toArray();
    }

    private function turmasDoAluno(string $alunoId): array
    {
        $aluno = Aluno::find($alunoId);

        return $aluno
            ? $aluno->turmas()->pluck('turmas.id')->toArray()
            : [];
    }

    /**
     * Disciplinas disponíveis para os selects (aula extra).
     * Vêm do landlord (central).
     */
    private function disciplinasDisponiveis(): array
    {
        return DisciplinaCentral::query()
            ->select('id', 'nome')
            ->orderBy('nome')
            ->get()
            ->toArray();
    }

    private function formatarHora($hora): ?string
    {
        if ($hora === null) {
            return null;
        }

        return is_string($hora) ? substr($hora, 0, 5) : $hora->format('H:i');
    }
}
