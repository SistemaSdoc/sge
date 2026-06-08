<?php

namespace App\Models;

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
    use HasFactory, HasUuid, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

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
        return $this->roles->contains('nome', 'Super Admin');
    }

    public function isDirector(): bool
    {
        return $this->roles->contains('nome', 'Director');
    }

    public function instituicaoFiltro(): ?string
    {
        if ($this->isSuperAdmin()) {
            return null;
        }

        return $this->instituicao_id;
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id')
            ->using(RoleUser::class)
            ->withTimestamps();
    }

    public function instituicao()
    {
        return $this->belongsTo(Instituicao::class, 'instituicao_id');
    }

   
}
