<?php

use App\Actions\Tenant\User\CreateUser;
use App\Actions\Tenant\User\UpdateUser;
use App\Models\Tenant\User;
use App\Notifications\User\UserCriadoNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    while (DB::transactionLevel() > 0) {
        DB::rollBack();
    }

    Artisan::call('migrate:fresh', [
        '--database' => 'sqlite',
        '--path' => database_path('migrations/tenant'),
        '--realpath' => true,
        '--no-interaction' => true,
    ]);
});

test('it creates a professor user profile and sends credentials notification', function () {
    Notification::fake();

    Role::findOrCreate('Professor', 'tenant');

    $user = User::factory()->create([
        'nome' => 'Admin Teste',
        'email' => 'admin@teste.local',
        'instituicao_id' => null,
    ]);

    $created = app(CreateUser::class)->handle([
        'nome' => 'Maria Professora',
        'email' => 'maria.professora@teste.local',
        'telefone' => '912345678',
        'password' => 'secret123',
        'roles' => ['Professor'],
        'instituicao_id' => $user->instituicao_id,
    ]);

    expect($created)->toBeInstanceOf(User::class)
        ->and($created->hasRole('Professor'))->toBeTrue();

    $this->assertDatabaseHas('users', ['email' => 'maria.professora@teste.local']);
    $this->assertDatabaseHas('professores', ['user_id' => $created->id]);

    Notification::assertSentTo($created, UserCriadoNotification::class, function (UserCriadoNotification $notification, array $channels) {
        return $notification->passwordPlain === 'secret123'
            && in_array('mail', $channels, true)
            && in_array('database', $channels, true);
    });
});

test('a director cannot remove their own role when updating their user profile', function (): void {
    $directorRole = Role::findOrCreate('Director', 'tenant');

    $director = User::factory()->create([
        'nome' => 'Director Teste',
        'email' => 'director.self-update@test.local',
        'instituicao_id' => null,
    ]);
    $director->assignRole($directorRole);

    expect(fn () => app(UpdateUser::class)->handle(
        $director,
        ['nome' => 'Director Alterado', 'roles' => []],
        $director,
    ))->toThrow(AuthorizationException::class);

    expect($director->fresh()->hasRole('Director'))->toBeTrue()
        ->and($director->fresh()->nome)->toBe('Director Teste');
});

test('profile updates without roles preserve the existing roles', function (): void {
    $directorRole = Role::findOrCreate('Director', 'tenant');
    $director = User::factory()->create([
        'nome' => 'Director Teste',
        'email' => 'director.roles-omitted@test.local',
    ]);
    $director->assignRole($directorRole);

    $updated = app(UpdateUser::class)->handle(
        $director,
        ['nome' => 'Director Actualizado'],
        $director,
    );

    expect($updated->hasRole('Director'))
        ->toBeTrue()
        ->and($updated->nome)->toBe('Director Actualizado');
});
