<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\CursoTuteladoProfessor;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Professor;
use App\Models\Tenant\User;
use App\Traits\NotificaProfessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class CursoTuteladoProfessorController extends Controller
{
    use NotificaProfessor;

    /**
     * Roles do grupo disciplinar (constant para evitar typos).
     */
    private const ROLE_MEMBRO = 'Membro do Grupo Disciplinar';
    private const ROLE_COORDENADOR = 'Coordenador do Grupo Disciplinar';

    // ============================================================
    // INDEX
    // ============================================================

    public function index(Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $instituicaoId = $user?->instituicaoFiltro();

        $professores = $cursoTutelado->professores()
            ->with(['user'])
            ->paginate(5);

        return response()->json(
            $professores->through(fn ($prof) => [
                'id'                => $prof->id,
                'nome'              => $prof->user?->nome,
                'email'             => $prof->user?->email,
                'tipo'              => $prof->pivot->tipo,
                'coordenador'       => $prof->pivot->coordenador,
                'opap'              => $prof->pivot->opap,
                'grupo_disciplinar' => $prof->pivot->grupo_disciplinar,
            ])
        );
    }

    // ============================================================
    // CREATE
    // ============================================================

    public function create(Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        $professores = Professor::with('user:id,nome')
            ->whereHas('user', fn ($q) => $q->where('instituicao_id', $instituicao->id))
            ->whereDoesntHave(
                'cursosTutelados',
                fn ($q) => $q->whereKey($cursoTutelado->getKey())
            )
            ->orderBy('id')
            ->get();

        return Inertia::render('tenant/cursos-tutelados/professores/create', [
            'professores'     => $professores,
            'instituicaoId'   => $instituicao->id,
            'cursoTuteladoId' => $cursoTutelado->id,
        ]);
    }

    // ============================================================
    // STORE
    // ============================================================

    public function store(Request $request, Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        $this->authorize('manageProfessores', $cursoTutelado);

        $request->validate([
            'professor_id'      => 'required|exists:professores,id',
            'tipo'              => 'required|in:principal,colaborador',
            'coordenador'       => 'boolean',
            'opap'              => 'boolean',
            'grupo_disciplinar' => 'nullable|in:nenhum,membro,coordenador',
        ]);

        // ── Validação: já tem coordenador de curso? ──
        if ($request->boolean('coordenador')) {
            $jaTemCoordenador = CursoTuteladoProfessor::where('curso_tutelado_id', $cursoTutelado->id)
                ->where('professor_id', '!=', $request->professor_id)
                ->where('coordenador', true)
                ->exists();

            if ($jaTemCoordenador) {
                return back()->withErrors([
                    'coordenador' => 'Este curso tutelado já tem um coordenador definido.',
                ]);
            }
        }

        // ── Validação: já tem coordenador de grupo disciplinar? ──
        if ($request->grupo_disciplinar === 'coordenador') {
            $jaTemCoordenadorGrupo = CursoTuteladoProfessor::where('curso_tutelado_id', $cursoTutelado->id)
                ->where('professor_id', '!=', $request->professor_id)
                ->where('grupo_disciplinar', 'coordenador')
                ->exists();

            if ($jaTemCoordenadorGrupo) {
                return back()->withErrors([
                    'grupo_disciplinar' => 'Este curso já tem um coordenador do grupo disciplinar.',
                ]);
            }
        }

        // ── Cria/atualiza o vínculo ──
        CursoTuteladoProfessor::updateOrCreate(
            [
                'curso_tutelado_id' => $cursoTutelado->id,
                'professor_id'      => $request->professor_id,
            ],
            [
                'tipo'              => $request->tipo,
                'coordenador'       => $request->boolean('coordenador'),
                'opap'              => $request->boolean('opap'),
                'grupo_disciplinar' => $request->grupo_disciplinar ?? 'nenhum',
            ]
        );

        // ── Sincroniza os roles do Spatie ──
        $professor = Professor::find($request->professor_id);
        $this->sincronizarRolesGrupoDisciplinar($professor->user, $request->grupo_disciplinar);

        $this->notificarProfessorAdicionadoAoCurso($professor, $cursoTutelado);

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.show', [
            'instituicao'   => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
        ]);
    }

    // ============================================================
    // SHOW (não usado)
    // ============================================================

    public function show(string $id)
    {
        //
    }

    // ============================================================
    // EDIT
    // ============================================================

    public function edit(Instituicao $instituicao, CursoTutelado $cursoTutelado, $professore)
    {
        $this->authorize('manageProfessores', $cursoTutelado);

        $vinculo = CursoTuteladoProfessor::query()
            ->with('professor.user:id,nome')
            ->where('curso_tutelado_id', $cursoTutelado->id)
            ->findOrFail($professore);

        return Inertia::render('tenant/cursos-tutelados/professores/edit', [
            'vinculo' => [
                'id'                => $vinculo->id,
                'professor_id'      => $vinculo->professor_id,
                'nome'              => $vinculo->professor?->user?->nome,
                'tipo'              => $vinculo->tipo,
                'coordenador'       => (bool) $vinculo->coordenador,
                'opap'              => (bool) $vinculo->opap,
                'grupo_disciplinar' => $vinculo->grupo_disciplinar ?? 'nenhum',
            ],
            'instituicaoId'   => $instituicao->id,
            'cursoTuteladoId' => $cursoTutelado->id,
        ]);
    }

    // ============================================================
    // UPDATE
    // ============================================================

    public function update(Request $request, Instituicao $instituicao, CursoTutelado $cursoTutelado, $professore)
    {
        $this->authorize('manageProfessores', $cursoTutelado);

        $request->validate([
            'tipo'              => 'required|in:principal,colaborador',
            'coordenador'       => 'boolean',
            'opap'              => 'boolean',
            'grupo_disciplinar' => 'nullable|in:nenhum,membro,coordenador',
        ]);

        if ($request->grupo_disciplinar === 'coordenador') {
            $jaTemCoordenadorGrupo = CursoTuteladoProfessor::where('curso_tutelado_id', $cursoTutelado->id)
                ->where('id', '!=', $professore)
                ->where('grupo_disciplinar', 'coordenador')
                ->exists();

            if ($jaTemCoordenadorGrupo) {
                return back()->withErrors([
                    'grupo_disciplinar' => 'Este curso já tem um coordenador do grupo disciplinar.',
                ]);
            }
        }

        $vinculo = CursoTuteladoProfessor::findOrFail($professore);

        $vinculo->update([
            'tipo'              => $request->tipo,
            'coordenador'       => $request->boolean('coordenador'),
            'opap'              => $request->boolean('opap'),
            'grupo_disciplinar' => $request->grupo_disciplinar ?? 'nenhum',
        ]);

        $professor = $vinculo->professor;
        $this->sincronizarRolesGrupoDisciplinar($professor->user, $request->grupo_disciplinar);

        return back();
    }

    // ============================================================
    // DESTROY
    // ============================================================

    public function destroy(Instituicao $instituicao, CursoTutelado $cursoTutelado, $professore)
    {
        $this->authorize('manageProfessores', $cursoTutelado);

        $vinculo = CursoTuteladoProfessor::query()
            ->where('curso_tutelado_id', $cursoTutelado->id)
            ->findOrFail($professore);

        $professor = $vinculo->professor;

        // Remove roles do grupo disciplinar ao desvincular
        if ($professor) {
            $this->sincronizarRolesGrupoDisciplinar($professor->user, 'nenhum');
        }

        $vinculo->delete();

        return back();
    }

    // ============================================================
    // AUXILIAR — Sincronizar roles do grupo disciplinar
    // ============================================================

    /**
     * Garante que o utilizador tem o role correto e remove os outros.
     *
     * - Cria os roles automaticamente se não existirem (evita RoleDoesNotExist).
     * - Remove com segurança apenas os roles que o utilizador tem.
     *
     * @param  User    $user
     * @param  string  $grupoDisciplinar  'nenhum' | 'membro' | 'coordenador' | null
     */
    private function sincronizarRolesGrupoDisciplinar(User $user, ?string $grupoDisciplinar): void
    {
        $grupoDisciplinar = $grupoDisciplinar ?? 'nenhum';

        // 1. Garantir que os roles existem (guard 'tenant')
        $roleMembro = Role::firstOrCreate(
            ['name' => self::ROLE_MEMBRO, 'guard_name' => 'tenant']
        );
        $roleCoordenador = Role::firstOrCreate(
            ['name' => self::ROLE_COORDENADOR, 'guard_name' => 'tenant']
        );

        // 2. Remover (só se o utilizador tiver — evita exceção)
        if ($user->hasRole($roleMembro->name)) {
            $user->removeRole($roleMembro);
        }
        if ($user->hasRole($roleCoordenador->name)) {
            $user->removeRole($roleCoordenador);
        }

        // 3. Atribuir o novo (se aplicável)
        match ($grupoDisciplinar) {
            'membro'      => $user->assignRole($roleMembro),
            'coordenador' => $user->assignRole($roleCoordenador),
            default       => null,
        };
    }
}