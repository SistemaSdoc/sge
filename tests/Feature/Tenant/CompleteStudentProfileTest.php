<?php

use App\Models\Tenant\Candidato;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
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

    Route::get('/test/student-area', fn () => response('Student area'))
        ->middleware(['web', 'auth:tenant', 'perfil.completo'])
        ->name('test.student-area');
});

it('redirects an incomplete student to the completion page', function () {
    $student = User::factory()->create();
    $student->assignRole(Role::findOrCreate('Aluno', 'tenant'));

    Candidato::create([
        'user_id' => $student->id,
        'nome' => $student->nome,
        'bi' => '010119999LA001',
        'email' => $student->email,
        'numero_estudante' => 'INS-TEST-0001',
    ]);

    $this->actingAs($student, 'tenant')
        ->get('/test/student-area')
        ->assertRedirect(route('tenant.perfil.completar.edit'));
});

it('lets an unverified student open the completion form', function () {
    $student = User::factory()->create(['email_verified_at' => null]);
    $student->assignRole(Role::findOrCreate('Aluno', 'tenant'));

    Candidato::create([
        'user_id' => $student->id,
        'nome' => $student->nome,
        'bi' => '010119999LA004',
        'email' => $student->email,
        'numero_estudante' => 'INS-TEST-0004',
    ]);

    $this->actingAs($student, 'tenant')
        ->get(route('tenant.perfil.completar.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tenant/perfil/completar')
            ->where('profile.numeroEstudante', 'INS-TEST-0004'));
});

it('saves required student data and unlocks the dashboard', function () {
    $student = User::factory()->create();
    $student->assignRole(Role::findOrCreate('Aluno', 'tenant'));

    $candidato = Candidato::create([
        'user_id' => $student->id,
        'nome' => $student->nome,
        'bi' => '010119999LA002',
        'email' => $student->email,
        'numero_estudante' => 'INS-TEST-0002',
    ]);

    $response = $this->actingAs($student, 'tenant')
        ->put(route('tenant.perfil.completar.update'), [
            'telefone' => '+244 923 000 000',
            'morada' => 'Rua da Escola, Luanda',
            'genero' => 'F',
            'nacionalidade' => 'Angolana',
            'naturalidade' => 'Luanda',
            'nome_pai' => 'João Silva',
            'nome_mae' => 'Maria Silva',
            'data_nascimento' => '2008-05-12',
            'municipio' => 'Belas',
        ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('tenant.dashboard'));

    expect($candidato->fresh()->temPerfilCompleto())->toBeTrue()
        ->and($candidato->fresh()->filiacao)->toBe('João Silva e Maria Silva')
        ->and($student->fresh()->telefone)->toBe('+244 923 000 000');

    $this->actingAs($student, 'tenant')
        ->get('/test/student-area')
        ->assertOk();
});

it('keeps an incomplete profile unchanged when required data is missing', function () {
    $student = User::factory()->create();
    $student->assignRole(Role::findOrCreate('Aluno', 'tenant'));

    $candidato = Candidato::create([
        'user_id' => $student->id,
        'nome' => $student->nome,
        'bi' => '010119999LA003',
        'email' => $student->email,
        'numero_estudante' => 'INS-TEST-0003',
    ]);

    $this->actingAs($student, 'tenant')
        ->put(route('tenant.perfil.completar.update'), [])
        ->assertSessionHasErrors([
            'telefone',
            'morada',
            'genero',
            'nacionalidade',
            'naturalidade',
            'nome_pai',
            'nome_mae',
            'data_nascimento',
            'municipio',
        ]);

    expect($candidato->fresh()->temPerfilCompleto())->toBeFalse()
        ->and($candidato->fresh()->perfil_completo)->toBeFalse();
});
