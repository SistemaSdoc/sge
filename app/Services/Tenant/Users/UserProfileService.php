<?php

namespace App\Services\Tenant\Users;

use App\Models\Tenant\User;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Features;

class UserProfileService
{
    /**
     * Prepara os dados resumidos do cabeçalho do perfil.
     *
     * @return array<string, mixed>
     */
    public function profileData(User $user): array
    {
        return [
            ...$user->only('id', 'nome', 'email', 'avatar'),
            'avatarUrl' => $user->avatar_url,
            'isAluno' => $user->hasRole('Aluno'),
        ];
    }

    /**
     * Prepara os dados pessoais do usuário ou do aluno.
     *
     * @return array<string, mixed>
     */
    public function personalData(User $user): array
    {
        $user->loadMissing('aluno.inscricao.candidato');

        if (! $user->hasRole('Aluno')) {
            return [
                'isAluno' => false,
                'basic' => [
                    'nome' => $user->nome,
                    'email' => $user->email,
                    'telefone' => $user->telefone,
                ],
            ];
        }

        $candidato = $user->aluno?->inscricao?->candidato;
        $filiacao = is_string($candidato?->filiacao)
            ? preg_split('/\s+e\s+/i', $candidato->filiacao, 2)
            : [];

        return [
            'isAluno' => true,
            'personal' => [
                'nome' => $candidato?->nome ?? $user->nome,
                'bi' => $candidato?->bi,
                'dataNascimento' => $candidato?->data_nascimento?->format('Y-m-d'),
                'genero' => $candidato?->genero,
                'nacionalidade' => $candidato?->nacionalidade,
                'naturalidade' => $candidato?->naturalidade,
            ],
            'parents' => [
                'pai' => $filiacao[0] ?? null,
                'mae' => $filiacao[1] ?? null,
            ],
            'address' => [
                'morada' => $candidato?->morada,
                'municipio' => $candidato?->municipio,
                'telefone' => $candidato?->telefone ?? $user->telefone,
                'email' => $candidato?->email ?? $user->email,
            ],
        ];
    }

    /**
     * Prepara os dados académicos do aluno.
     *
     * @return array<string, mixed>
     */
    public function academicData(User $user): array
    {
        $user->load([
            'aluno.inscricao.cursoClasseTurno.turno',
            'aluno.inscricao.cursoClasseTurno.cursoClasse.classe',
            'aluno.inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
        ]);

        $aluno = $user->aluno;
        $inscricao = $aluno?->inscricao;
        $cursoClasseTurno = $inscricao?->cursoClasseTurno;
        $cursoClasse = $cursoClasseTurno?->cursoClasse;
        $turma = $aluno?->turmas()
            ->wherePivot('activo', true)
            ->with(['cursoClasseTurno.cursoClasse.classe', 'anoLectivo'])
            ->latest('turmas.created_at')
            ->first();

        return [
            'academic' => [
                'matricula' => $aluno?->matricula,
                'numeroProcesso' => $aluno?->numero_processo,
                'curso' => $cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome,
                'classe' => $cursoClasse?->classe?->nome,
                'turno' => $cursoClasseTurno?->turno?->nome,
                'turma' => $turma?->nome,
                'anoLectivo' => $turma?->anoLectivo?->nome ?? $inscricao?->anoLectivo?->nome,
            ],
        ];
    }

    /**
     * Prepara os dados da página de segurança.
     *
     * @return array<string, mixed>
     */
    public function securityData(User $user): array
    {
        $canManageTwoFactor = Features::canManageTwoFactorAuthentication();
        $canManagePasskeys = Features::canManagePasskeys();

        return [
            'user' => $this->profileData($user),
            'hasPassword' => ! is_null($user->password),
            'canManageTwoFactor' => $canManageTwoFactor,
            'canManagePasskeys' => $canManagePasskeys,
            'passkeys' => $canManagePasskeys
                ? $user->passkeys()
                    ->select(['id', 'name', 'credential', 'created_at', 'last_used_at'])
                    ->latest()
                    ->get()
                    ->map(fn ($passkey): array => [
                        'id' => $passkey->id,
                        'name' => $passkey->name,
                        'authenticator' => $passkey->authenticator,
                        'created_at_diff' => $passkey->created_at->diffForHumans(),
                        'last_used_at_diff' => $passkey->last_used_at?->diffForHumans(),
                    ])
                    ->values()
                    ->all()
                : [],
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ];
    }
}
