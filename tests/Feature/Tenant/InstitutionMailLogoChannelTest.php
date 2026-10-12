<?php

use App\Models\Central\Tenant;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\User;
use App\Notifications\Channels\InstitutionLogoMailChannel;
use App\Notifications\Tenant\ResetPasswordNotification;
use Illuminate\Mail\SentMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

function createTenantForInstitutionMailLogoChannelTest(string $id): Tenant
{
    $tenant = Tenant::create(['id' => $id, 'status' => 'active']);
    File::put(database_path($tenant->database()->getName()), '');

    Artisan::call('tenants:migrate', [
        '--tenants' => [$tenant->id],
        '--force' => true,
        '--no-interaction' => true,
    ]);

    return $tenant->refresh();
}

beforeEach(function (): void {
    Artisan::call('migrate:fresh', [
        '--database' => 'sqlite',
        '--path' => database_path('migrations'),
        '--realpath' => true,
        '--no-interaction' => true,
    ]);

});

afterEach(function (): void {
    tenancy()->end();

    $tenant = Tenant::query()->find('mail-logo-channel-test');

    if ($tenant) {
        File::delete(database_path($tenant->database()->getName()));
        $tenant->delete();
    }
});

test('tenant reset password email resolves institution from tenant database', function (): void {
    config(['mail.default' => 'array']);
    $tenant = createTenantForInstitutionMailLogoChannelTest('mail-logo-channel-test');

    $institution = $tenant->run(fn (): Instituicao => Instituicao::create([
        'nome' => 'Instituição do Tenant',
        'tipo' => 'colegio',
    ]));
    $tenant->update(['instituicao_id' => $institution->id]);

    $user = $tenant->run(fn (): User => User::create([
        'nome' => 'Utilizador de teste',
        'email' => 'tenant-reset@example.test',
        'password' => 'password',
    ]));

    $sentMessage = $tenant->run(fn (): ?SentMessage => app(InstitutionLogoMailChannel::class)->send(
        $user,
        new ResetPasswordNotification('reset-token'),
    ));

    expect($sentMessage)->toBeInstanceOf(SentMessage::class);
});

test('tenant mail includes the institution logo inline when institution data is not passed to the view', function (): void {
    config(['mail.default' => 'array']);
    $tenant = createTenantForInstitutionMailLogoChannelTest('mail-logo-channel-test');

    $institution = $tenant->run(fn (): Instituicao => Instituicao::create([
        'nome' => 'Instituição do Tenant',
        'tipo' => 'colegio',
        'logo' => 'logos/institution.png',
    ]));
    $tenant->update(['instituicao_id' => $institution->id]);

    $user = $tenant->run(fn (): User => User::create([
        'nome' => 'Utilizador de teste',
        'email' => 'tenant-logo@example.test',
        'password' => 'password',
    ]));

    $tenant->run(fn () => Storage::disk('public')->put('logos/institution.png', 'logo-content'));

    $notification = new class extends Notification
    {
        public function via(object $notifiable): array
        {
            return ['mail'];
        }

        public function toMail(object $notifiable): MailMessage
        {
            return (new MailMessage)
                ->subject('Logo da instituição')
                ->view('mail.partials.institution-logo');
        }
    };

    $sentMessage = $tenant->run(fn (): ?SentMessage => app(InstitutionLogoMailChannel::class)->send(
        $user,
        $notification,
    ));

    expect($sentMessage)->toBeInstanceOf(SentMessage::class)
        ->and($sentMessage->toString())
        ->toContain('cid:institution-logo@pge.edu.ao')
        ->toContain('Content-ID: <institution-logo@pge.edu.ao>');
});

test('mail is still sent when tenant institution table is unavailable', function (): void {
    config(['mail.default' => 'array']);
    $tenant = createTenantForInstitutionMailLogoChannelTest('mail-logo-channel-test');

    $user = $tenant->run(fn (): User => User::create([
        'nome' => 'Utilizador de teste',
        'email' => 'tenant-reset@example.test',
        'password' => 'password',
    ]));

    $tenant->run(fn () => Schema::dropIfExists('instituicoes'));

    $sentMessage = $tenant->run(fn (): ?SentMessage => app(InstitutionLogoMailChannel::class)->send(
        $user,
        new ResetPasswordNotification('reset-token'),
    ));

    expect($sentMessage)->toBeInstanceOf(SentMessage::class);
});

test('mail is still sent when institution logo contents are empty', function (): void {
    config(['mail.default' => 'array']);
    $tenant = createTenantForInstitutionMailLogoChannelTest('mail-logo-channel-test');

    $institution = $tenant->run(fn (): Instituicao => Instituicao::create([
        'nome' => 'Instituição do Tenant',
        'tipo' => 'colegio',
        'logo' => 'empty-logo.png',
    ]));
    $tenant->update(['instituicao_id' => $institution->id]);

    $user = $tenant->run(fn (): User => User::create([
        'nome' => 'Utilizador de teste',
        'email' => 'tenant-reset-empty-logo@example.test',
        'password' => 'password',
    ]));

    $tenant->run(fn () => Storage::disk('public')->put('empty-logo.png', ''));

    $sentMessage = $tenant->run(fn (): ?SentMessage => app(InstitutionLogoMailChannel::class)->send(
        $user,
        new ResetPasswordNotification('reset-token'),
    ));

    expect($sentMessage)->toBeInstanceOf(SentMessage::class);
});
