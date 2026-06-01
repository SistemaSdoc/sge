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

    /**
     * Get all abilities for the user (permissions across all resources)
     * Used for Inertia React frontend authorization
     */
    public function getAbilities(): array
    {
        return [
            // Classes
            'classes' => [
                'create' => $this->can('create', Classe::class),
                'view' => $this->can('viewAny', Classe::class),
                'edit' => $this->can('update', Classe::class),
                'delete' => $this->can('delete', Classe::class),
            ],

            // Turnos
            'turnos' => [
                'create' => $this->can('create', Turno::class),
                'view' => $this->can('viewAny', Turno::class),
                'edit' => $this->can('update', Turno::class),
                'delete' => $this->can('delete', Turno::class),
            ],

            // Disciplinas
            'disciplinas' => [
                'create' => $this->can('create', Disciplina::class),
                'view' => $this->can('viewAny', Disciplina::class),
                'edit' => $this->can('update', Disciplina::class),
                'delete' => $this->can('delete', Disciplina::class),
            ],

            // Cursos
            'cursos' => [
                'create' => $this->can('create', Curso::class),
                'view' => $this->can('viewAny', Curso::class),
                'edit' => $this->can('update', Curso::class),
                'delete' => $this->can('delete', Curso::class),
            ],

            // Users
            'users' => [
                'create' => $this->can('create', User::class),
                'view' => $this->can('viewAny', User::class),
                'edit' => $this->can('update', User::class),
                'delete' => $this->can('delete', User::class),
            ],

            // Alunos
            'alunos' => [
                'create' => $this->can('create', Aluno::class),
                'view' => $this->can('viewAny', Aluno::class),
                'edit' => $this->can('update', Aluno::class),
                'delete' => $this->can('delete', Aluno::class),
            ],

            // Professores
            'professores' => [
                'create' => $this->can('create', Professor::class),
                'view' => $this->can('viewAny', Professor::class),
                'edit' => $this->can('update', Professor::class),
                'delete' => $this->can('delete', Professor::class),
            ],

            // Instituições
            'instituicoes' => [
                'create' => $this->can('create', Instituicao::class),
                'view' => $this->can('viewAny', Instituicao::class),
                'edit' => $this->can('update', Instituicao::class),
                'delete' => $this->can('delete', Instituicao::class),
            ],
        ];
    }
}
