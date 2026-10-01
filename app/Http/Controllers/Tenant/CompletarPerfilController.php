<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Aluno\CompleteStudentProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CompleteStudentProfileRequest;
use App\Models\Tenant\Candidato;
use App\Models\Tenant\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CompletarPerfilController extends Controller
{
    public function __construct(private readonly CompleteStudentProfile $completeStudentProfile) {}

    /**
     * Mostra o formulário de conclusão dos dados pessoais do aluno.
     */
    public function edit(): Response|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $candidato = $user->candidatoDoAluno();

        if ($candidato?->temPerfilCompleto()) {
            return to_route('tenant.dashboard');
        }

        if (! $candidato) {
            return Inertia::render('tenant/users/profile/completar/index', [
                'profile' => null,
            ]);
        }

        $filiacao = preg_split('/\s+e\s+/i', (string) $candidato->filiacao, 2) ?: [];

        return Inertia::render('tenant/users/profile/completar/index', [
            'profile' => [
                'nome' => $candidato->nome,
                'bi' => $candidato->bi,
                'numeroEstudante' => $candidato->numero_estudante,
                'telefone' => $candidato->telefone,
                'morada' => $candidato->morada,
                'genero' => $candidato->genero,
                'nacionalidade' => $candidato->nacionalidade,
                'naturalidade' => $candidato->naturalidade,
                'nomePai' => $filiacao[0] ?? null,
                'nomeMae' => $filiacao[1] ?? null,
                'dataNascimento' => $candidato->getRawOriginal('data_nascimento'),
                'municipio' => $candidato->municipio,
            ],
        ]);
    }

    /**
     * Guarda os dados pessoais obrigatórios do aluno.
     */
    public function update(CompleteStudentProfileRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $candidato = $user->candidatoDoAluno();

        abort_unless($candidato instanceof Candidato, 404);

        $this->completeStudentProfile->handle(
            $user,
            $candidato,
            $request->validated()
        );

        return to_route('tenant.dashboard');
    }
}
