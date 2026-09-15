<?php

use App\Actions\Tenant\User\CreateUser;
use App\Models\Tenant\User;
use App\Notifications\User\UserCriadoNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

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
