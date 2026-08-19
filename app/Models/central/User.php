<?php

namespace App\Models\central;

use App\Traits\HasUuid;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'instituicao_id',
    'nome',
    'email',
    'bi',
    'telefone',
    'password',
    'google_id',
    'facebook_id',
    'avatar',
])]

#[Hidden([
    'password',
    'two_factor_secret',
    'two_factor_recovery_codes',
    'remember_token',
])]

class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, HasUuid, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function professor()
    {
        return $this->hasOne(Professor::class);
    }

    public function aluno()
    {
        return $this->hasOne(Aluno::class);
    }

    public function candidato()
    {
        return $this->hasOne(Candidato::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('SuperAdmin'); // usa o método do Spatie
    }

    public function isDirector(): bool
    {
        return $this->hasRole('Director'); // usa o método do Spatie
    }

    /**
     * Retorna a rota de redirecionamento baseada no role do utilizador
     */
    public function roleRedirectPath(): string
    {
        // Se é candidato ou aluno, redireciona para portal
        if ($this->hasRole('Candidato')) {
            return '/portal';
        }

        // Qualquer outro role (admin, director, etc.) vai para dashboard
        return '/dashboard';
    }

    public function instituicaoFiltro(): ?string
    {
        if ($this->isSuperAdmin()) {
            return null;
        }

        return $this->instituicao_id;
    }

    public function instituicao()
    {
        return $this->belongsTo(Instituicao::class, 'instituicao_id');
    }
}
