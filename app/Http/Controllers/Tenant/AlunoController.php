<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Aluno\DeleteAluno;
use App\Actions\Tenant\Aluno\PrepareAlunoEdit;
use App\Actions\Tenant\Aluno\PrepareAlunoIndex;
use App\Actions\Tenant\Aluno\PrepareAlunoShow;
use App\Actions\Tenant\Aluno\UpdateAluno;
use App\Exceptions\UserRemovalBlockedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Aluno\UpdateAlunoRequest;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\User;
use App\Services\Tenant\VerificadorPropinaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AlunoController extends Controller
{
    public function __construct(
        private readonly DeleteAluno $deleteAluno,
        private readonly PrepareAlunoEdit $prepareAlunoEdit,
        private readonly PrepareAlunoIndex $prepareAlunoIndex,
        private readonly PrepareAlunoShow $prepareAlunoShow,
        private readonly UpdateAluno $updateAluno,
    ) {}

    /** Lista os alunos visíveis para o utilizador autenticado. */
    public function index(Request $request, VerificadorPropinaService $verificador)
    {
        Gate::authorize('viewAny', Aluno::class);

        Inertia::flash('toast', [
            'type' => 'error',
            'message' => 'Não é possível remover o aluno, pois existem registos associados que impedem a sua remoção.',
        ]);

        /** @var User $user */
        $user = Auth::user();

        return Inertia::render('tenant/alunos/index', [
            ...$this->prepareAlunoIndex->handle(
                $request,
                $user,
                $verificador
            ),
        ]);
    }

    /** Mostra o perfil académico e pessoal do aluno. */
    public function show(Aluno $aluno, Request $request)
    {
        Gate::authorize('view', $aluno);

        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        return Inertia::render('tenant/alunos/show', [
            ...$this->prepareAlunoShow->handle($aluno, $request, $user),
        ]);
    }

    /** Mostra o formulário de edição do aluno. */
    public function edit(Aluno $aluno)
    {
        Gate::authorize('update', $aluno);

        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        return Inertia::render('tenant/alunos/edit', $this->prepareAlunoEdit->handle(
            $aluno,
            $user
        ));
    }

    /** Valida e actualiza os dados do aluno. */
    public function update(UpdateAlunoRequest $request, Aluno $aluno)
    {
        $this->updateAluno->handle($aluno, $request->validated());

        return to_route('tenant.dashboard.alunos.index')->with('toast', [
            'type' => 'success',
            'message' => 'Aluno atualizado com sucesso!',
        ]);
    }

    /** Remove o aluno ou apresenta a razão que impede a remoção. */
    public function destroy(Aluno $aluno): RedirectResponse
    {
        $authorization = Gate::inspect('delete', $aluno);

        if ($authorization->denied()) {
            /** @var User|null $user */
            $user = Auth::guard('tenant')->user();
            abort_unless($user?->isSuperAdmin(), 403);

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $authorization->message(),
            ]);

            return back();
        }

        try {
            $this->deleteAluno->handle($aluno);
        } catch (UserRemovalBlockedException $exception) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $exception->getMessage(),
            ]);

            return back();
        }

        return to_route('tenant.dashboard.alunos.index')->with('toast', [
            'type' => 'success',
            'message' => 'Aluno removido com sucesso!',
        ]);
    }
}
