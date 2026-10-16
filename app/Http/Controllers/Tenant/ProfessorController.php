<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Professor\CreateProfessor;
use App\Actions\Tenant\Professor\DeleteProfessor;
use App\Actions\Tenant\Professor\UpdateProfessor;
use App\Exceptions\UserRemovalBlockedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Professor\StoreProfessoresRequest;
use App\Http\Requests\Tenant\Professor\UpdateProfessoresRequest;
use App\Models\Central\AnoLectivo;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ProfessorController extends Controller
{
    public function __construct(
        private readonly CreateProfessor $createProfessor,
        private readonly UpdateProfessor $updateProfessor,
        private readonly DeleteProfessor $deleteProfessor,
    ) {}

    /**
     * Mostra a lista de professores da instituição do usuario.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Professor::class);

        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        $instituicaoId = $user?->instituicaoFiltro();

        $professores = Professor::select([
            'id',
            'user_id',
            'especialidade',
            'nivel_academico',
            'created_at',
        ])
            ->with(['user:id,nome,telefone'])
            ->search($request->string('search')->toString())
            ->when(
                $instituicaoId,
                fn ($q) => $q->whereHas(
                    'user',
                    fn ($q) => $q->where('instituicao_id', $instituicaoId)
                )
            )
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('tenant/professores/index', [
            'professores' => $professores,
            'filters' => $request->only('search'),
        ]);
    }

    /**
     * Mostra o formulário para criar um novo professor.
     */
    public function create()
    {
        $this->authorize('create', Professor::class);

        return Inertia::render('tenant/professores/create');
    }

    /**
     * Guarda um novo professor no tenant actual.
     */
    public function store(StoreProfessoresRequest $request)
    {
        $this->authorize('create', Professor::class);

        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $this->createProfessor->handle(
            $user,
            $request->validated(),
        );

        return to_route('tenant.dashboard.professores.index')->with('toast', [
            'type' => 'success',
            'message' => 'Professor criado com sucesso.',
        ]);
    }

    /**
     * Mostra os dados, cursos e turmas de um professor.
     */
    public function show(Professor $professor)
    {
        $this->authorize('view', $professor);

        $anoLectivoId = request('ano_lectivo_id')
            ?? AnoLectivo::activo()?->id;

        $professor->load([
            'user:id,nome,email,bi,telefone',
            'turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina:id,nome',
            'turmaDisciplinaProfessor.classeTurnoDisciplina.cursoClasseTurno.turno:id,nome',
            'cursosTutelados.instituicaoCurso.curso:id,nome',
        ]);

        $cursos = $professor->cursosTutelados->map(function ($ct) {
            $curso = $ct->instituicaoCurso?->curso;
            if (! $curso) {
                return null;
            }

            return ['id' => $curso->id, 'nome' => $curso->nome];
        })->filter()->unique('id')->values();

        $turmas = Turma::with('cursoClasseTurno.cursoClasse.classe:id,nome')
            ->whereHas('turmaDisciplinaProfessor', fn ($q) => $q->where('professor_id', $professor->id))
            ->where('ano_lectivo_id', $anoLectivoId)   // ← direto na turma
            ->get()
            ->map(fn ($turma) => [
                'id' => $turma->id,
                'nome' => $turma->nome,
                'classe' => $turma->cursoClasseTurno?->cursoClasse?->classe?->nome,
                'turno' => $turma->cursoClasseTurno?->turno?->nome,
            ]);

        return Inertia::render('tenant/professores/show', [
            'professor' => $professor,
            'cursos' => $cursos,
            'turmas' => $turmas,
            'anoLectivoId' => $anoLectivoId,
            'anosLectivos' => AnoLectivo::all(),
        ]);
    }

    /**
     * Mostra o formulário para editar um professor específico.
     */
    public function edit(Professor $professor)
    {
        $this->authorize('update', $professor);

        return Inertia::render('tenant/professores/edit', [
            'professor' => $professor->load('user:id,nome,email,bi,telefone'),
        ]);
    }

    /**
     * Actualiza os dados de um professor específico.
     */
    public function update(UpdateProfessoresRequest $request, Professor $professor)
    {
        $this->authorize('update', $professor);

        $this->updateProfessor->handle($professor, $request->validated());

        return to_route('tenant.dashboard.professores.index')->with('toast', [
            'type' => 'success',
            'message' => 'Professor atualizado com sucesso.',
        ]);
    }

    /**
     * Remove um professor específico.
     */
    public function destroy(Professor $professor): RedirectResponse
    {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('delete', $professor);

        try {
            $this->deleteProfessor->handle($professor);
        } catch (UserRemovalBlockedException $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Professor removido com sucesso.',
        ]);

        return to_route('tenant.dashboard.professores.index');
    }
}
