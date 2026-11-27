<?php

use App\Http\Middleware\Tenant\EnsureEnrollmentNotCancelled;
use App\Models\Tenant\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware([
        InitializeTenancyByDomain::class,
        PreventAccessFromCentralDomains::class,
    ]);

    if (! Schema::hasColumn('users', 'deleted_at')) {
        Schema::table('users', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    Schema::create('inscricoes', function (Blueprint $table): void {
        $table->uuid('id')->primary();
        $table->string('status');
    });

    Schema::create('alunos', function (Blueprint $table): void {
        $table->uuid('id')->primary();
        $table->uuid('inscricao_id');
        $table->uuid('user_id')->nullable();
        $table->softDeletes();
    });

    Route::get('/test/enrollment-access', fn () => response('ok'))
        ->middleware(['web', 'auth:tenant', EnsureEnrollmentNotCancelled::class]);
});

function createTenantUserWithEnrollment(string $status): User
{
    $user = User::factory()->create();
    $enrollmentId = (string) Str::uuid();

    DB::statement('PRAGMA foreign_keys = OFF');

    try {
        DB::table('inscricoes')->insert([
            'id' => $enrollmentId,
            'status' => $status,
        ]);

        DB::table('alunos')->insert([
            'id' => (string) Str::uuid(),
            'inscricao_id' => $enrollmentId,
            'user_id' => $user->id,
        ]);
    } finally {
        DB::statement('PRAGMA foreign_keys = ON');
    }

    return $user;
}

it('rejects password login for a user with a cancelled enrollment', function (): void {
    $user = createTenantUserWithEnrollment('cancelado');

    $this->post(route('tenant.login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest('tenant');
});

it('shows Portuguese validation messages for invalid login fields', function (): void {
    $this->post(route('tenant.login.store'), [
        'email' => 'invalido',
        'password' => '',
    ])->assertSessionHasErrors([
        'email' => 'Introduza um endereço de email válido.',
        'password' => 'A palavra-passe é obrigatória.',
    ]);
});

it('ends an authenticated session when the enrollment is cancelled', function (): void {
    $user = createTenantUserWithEnrollment('cancelado');

    $this->actingAs($user, 'tenant')
        ->get('/test/enrollment-access')
        ->assertRedirect(route('tenant.login'));

    $this->assertGuest('tenant');
});

it('allows password login when the enrollment is not cancelled', function (): void {
    $user = createTenantUserWithEnrollment('aprovado');

    $this->post(route('tenant.login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard'));

    $this->assertAuthenticatedAs($user, 'tenant');
});
