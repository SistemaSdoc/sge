<?php

use App\Models\Tenant\Candidato;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware([
        InitializeTenancyByDomain::class,
        PreventAccessFromCentralDomains::class,
    ]);

    while (DB::transactionLevel() > 0) {
        DB::rollBack();
    }

    Artisan::call('migrate:fresh', [
        '--database' => 'sqlite',
        '--path' => database_path('migrations/tenant/0006_01_01_000000_create_users_table.php'),
        '--realpath' => true,
        '--no-interaction' => true,
    ]);

    Artisan::call('migrate', [
        '--database' => 'sqlite',
        '--path' => database_path('migrations/tenant/2026_07_05_071936_create_permission_tables.php'),
        '--realpath' => true,
        '--no-interaction' => true,
    ]);

    Artisan::call('migrate', [
        '--database' => 'sqlite',
        '--path' => database_path('migrations/tenant/2026_09_17_151910_alter_candidatos_minimal_inscricao.php'),
        '--realpath' => true,
        '--no-interaction' => true,
    ]);
});

it('requires every personal field when a student updates their profile', function () {
    $student = User::factory()->create();
    $student->assignRole(Role::findOrCreate('Aluno', 'tenant'));

    Candidato::create([
        'user_id' => $student->id,
        'nome' => $student->nome,
        'bi' => '010119999LA901',
        'email' => $student->email,
        'numero_estudante' => 'INS-TEST-0901',
        'telefone' => '+244923000901',
        'morada' => 'Rua da Escola',
        'genero' => 'F',
        'nacionalidade' => 'Angolana',
        'naturalidade' => 'Luanda',
        'filiacao' => 'João Silva e Maria Silva',
        'data_nascimento' => '2008-05-12',
        'municipio' => 'Belas',
        'perfil_completo' => true,
    ]);

    $this->actingAs($student, 'tenant')
        ->put("/dashboard/users/{$student->id}/personal", [
            'nome' => $student->nome,
            'email' => $student->email,
        ])
        ->assertSessionHasErrors([
            'bi',
            'genero',
            'data_nascimento',
            'nacionalidade',
            'naturalidade',
            'nome_pai',
            'nome_mae',
            'morada',
            'municipio',
            'telefone',
        ]);
});

it('allows non-students to update only their basic profile fields', function () {
    $staff = User::factory()->create();
    $staff->assignRole(Role::findOrCreate('Professor', 'tenant'));

    $this->actingAs($staff, 'tenant')
        ->put("/dashboard/users/{$staff->id}/personal", [
            'nome' => 'Professor Atualizado',
            'email' => $staff->email,
        ])
        ->assertSessionHasNoErrors();

    expect($staff->fresh()->nome)->toBe('Professor Atualizado');
});