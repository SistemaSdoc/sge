<?php

use App\Http\Controllers\Tenant\NotasTwoFactorController;
use App\Http\Middleware\RequireNotesTwoFactor;
use App\Models\Tenant\User;
use App\Notifications\Tenant\NotesAccessCodeNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Route::middleware(['web', 'auth:tenant'])->group(function (): void {
        Route::get('/_test/notes-two-factor-challenge', [NotasTwoFactorController::class, 'create']);
        Route::post('/_test/notes-two-factor-challenge', [NotasTwoFactorController::class, 'verify']);
        Route::post('/_test/notes-two-factor-challenge/resend', [NotasTwoFactorController::class, 'resend']);
        Route::get('/_test/protected-notes', fn () => response('notes'))
            ->middleware(RequireNotesTwoFactor::class);
        Route::post('/_test/protected-notes', fn () => response('notes saved'))
            ->middleware(RequireNotesTwoFactor::class);
        Route::get('/_test/protected-history', fn () => response('history'))
            ->middleware(RequireNotesTwoFactor::class.':history');
        Route::get('/_test/pauta-export', fn () => response('export'))
            ->middleware(RequireNotesTwoFactor::class.':pauta');
    });
});

function notesEmailChallengeUser(): User
{
    return (new User)->forceFill([
        'id' => (string) Str::uuid(),
        'nome' => 'Professor Teste',
        'email' => 'teacher@example.test',
    ]);
}

function notesEmailChallengeCode(User $user): string
{
    $code = null;

    Notification::assertSentTo(
        $user,
        NotesAccessCodeNotification::class,
        function (NotesAccessCodeNotification $notification) use (&$code): bool {
            $code = $notification->code;

            return true;
        },
    );

    return $code;
}

it('sends a code and displays the masked account email', function (): void {
    Notification::fake();
    $user = notesEmailChallengeUser();

    $this->actingAs($user, 'tenant')
        ->get('/_test/notes-two-factor-challenge')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tenant/auth/notes-two-factor-challenge')
            ->where('email', 't******@example.test')
        );

    Notification::assertSentTo($user, NotesAccessCodeNotification::class);

    $code = notesEmailChallengeCode($user);
    $mail = (new NotesAccessCodeNotification($code))->toMail($user);
    $html = view($mail->view, $mail->viewData)->render();

    expect($html)
        ->toContain($code)
        ->toContain('Confirme o acesso às notas')
        ->toContain('Código válido por 10 minutos');
});

it('rejects an incorrect code without authorizing the session', function (): void {
    Notification::fake();
    $user = notesEmailChallengeUser();

    $this->actingAs($user, 'tenant')->get('/_test/notes-two-factor-challenge');
    $code = notesEmailChallengeCode($user);
    $incorrectCode = $code === '000000' ? '000001' : '000000';

    $this->from('/_test/notes-two-factor-challenge')
        ->post('/_test/notes-two-factor-challenge', ['code' => $incorrectCode])
        ->assertSessionHasErrors('code')
        ->assertSessionMissing('notes_access.authorizations.discipline');
});

it('authorizes a valid code once and returns to the protected destination', function (): void {
    Notification::fake();
    $user = notesEmailChallengeUser();

    $this->actingAs($user, 'tenant')
        ->get('/_test/notes-two-factor-challenge?return_to=%2F_test%2Fprotected-notes');

    $code = notesEmailChallengeCode($user);

    $this->post('/_test/notes-two-factor-challenge', ['code' => $code])
        ->assertRedirect('/_test/protected-notes')
        ->assertSessionHas('notes_access.authorizations.discipline');

    $this->post('/_test/notes-two-factor-challenge', ['code' => $code])
        ->assertSessionHasErrors('code');
});

it('requires confirmation again when the session authorization expires', function (): void {
    $user = notesEmailChallengeUser();

    $this->actingAs($user, 'tenant')
        ->get('/_test/protected-notes')
        ->assertRedirect(route('tenant.dashboard.notes.two-factor.challenge', ['scope' => 'discipline']))
        ->assertSessionHas('notes_access.return_to', '/_test/protected-notes');

    $this->withSession([
        'notes_access.authorizations.discipline' => [
            'expires_at' => now()->addMinute()->timestamp,
            'user_id' => $user->getAuthIdentifier(),
            'tenant_id' => (string) (tenancy()->tenant?->getTenantKey() ?? ''),
        ],
    ])
        ->get('/_test/protected-notes')
        ->assertOk()
        ->assertSeeText('notes');
});

it('keeps the authorization active while the user continues on the same page', function (): void {
    $user = notesEmailChallengeUser();
    $expiredAt = now()->subMinute()->timestamp;

    $this->actingAs($user, 'tenant')
        ->withSession([
            'notes_access.authorizations.discipline' => [
                'expires_at' => $expiredAt,
                'active_path' => '/_test/protected-notes',
                'user_id' => $user->getAuthIdentifier(),
                'tenant_id' => (string) (tenancy()->tenant?->getTenantKey() ?? ''),
            ],
        ])
        ->withHeader('referer', url('/_test/protected-notes'))
        ->get('/_test/protected-notes')
        ->assertOk()
        ->assertSeeText('notes');

    expect(session('notes_access.authorizations.discipline.expires_at'))
        ->toBeGreaterThan(now()->timestamp);

    $this->withHeader('referer', url('/_test/protected-notes'))
        ->post('/_test/protected-notes')
        ->assertOk()
        ->assertSeeText('notes saved');
});

it('requires confirmation after returning to the page from elsewhere', function (): void {
    $user = notesEmailChallengeUser();

    $this->actingAs($user, 'tenant')
        ->withSession([
            'notes_access.authorizations.discipline' => [
                'expires_at' => now()->subMinute()->timestamp,
                'active_path' => '/_test/protected-notes',
                'user_id' => $user->getAuthIdentifier(),
                'tenant_id' => (string) (tenancy()->tenant?->getTenantKey() ?? ''),
            ],
        ])
        ->withHeader('referer', url('/_test/dashboard'))
        ->get('/_test/protected-notes')
        ->assertRedirect(route('tenant.dashboard.notes.two-factor.challenge', ['scope' => 'discipline']))
        ->assertSessionHas('notes_access.return_to', '/_test/protected-notes');
});

it('rejects an expired code', function (): void {
    Notification::fake();
    $user = notesEmailChallengeUser();

    $this->actingAs($user, 'tenant')->get('/_test/notes-two-factor-challenge');
    $code = notesEmailChallengeCode($user);
    $this->travel(11)->minutes();

    $this->post('/_test/notes-two-factor-challenge', ['code' => $code])
        ->assertSessionHasErrors('code')
        ->assertSessionMissing('notes_access.challenge');
});

it('shows a validation error instead of returning 429 when resend limit is reached', function (): void {
    Notification::fake();
    $user = notesEmailChallengeUser();

    $this->actingAs($user, 'tenant')->get('/_test/notes-two-factor-challenge');

    $limiterKey = 'notes-email-code:'.(string) (tenancy()->tenant?->getTenantKey() ?? '').':'.$user->getAuthIdentifier();

    for ($attempt = 0; $attempt < 4; $attempt++) {
        RateLimiter::hit($limiterKey, 900);
    }

    $this->travel(61)->seconds();

    $this->from('/_test/notes-two-factor-challenge')
        ->post('/_test/notes-two-factor-challenge/resend')
        ->assertRedirect('/_test/notes-two-factor-challenge')
        ->assertSessionHasErrors('resend');

    $this->travel(11)->minutes();

    $this->get('/_test/notes-two-factor-challenge')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('sendErrorType', 'limit')
            ->where('canVerify', false)
            ->where('challengeExpired', true)
        );
});

it('does not redirect to an external URL after verification', function (): void {
    Notification::fake();
    $user = notesEmailChallengeUser();

    $this->actingAs($user, 'tenant')
        ->get('/_test/notes-two-factor-challenge?return_to=https%3A%2F%2Fevil.example');

    $code = notesEmailChallengeCode($user);

    $this->post('/_test/notes-two-factor-challenge', ['code' => $code])
        ->assertRedirect(route('tenant.dashboard'));
});

it('does not reuse a confirmation after the authenticated user changes', function (): void {
    $user = notesEmailChallengeUser();
    $otherUser = notesEmailChallengeUser();

    $this->actingAs($user, 'tenant')
        ->withSession([
            'notes_access.authorizations.discipline' => [
                'expires_at' => now()->addMinute()->timestamp,
                'user_id' => $user->getAuthIdentifier(),
                'tenant_id' => (string) (tenancy()->tenant?->getTenantKey() ?? ''),
            ],
        ])
        ->get('/_test/protected-notes')
        ->assertOk();

    $this->actingAs($otherUser, 'tenant')
        ->get('/_test/protected-notes')
        ->assertRedirect(route('tenant.dashboard.notes.two-factor.challenge', ['scope' => 'discipline']))
        ->assertSessionHas('notes_access.return_to', '/_test/protected-notes');
});

it('does not reuse discipline authorization for historical grades', function (): void {
    $user = notesEmailChallengeUser();

    $this->actingAs($user, 'tenant')
        ->withSession([
            'notes_access.authorizations.discipline' => [
                'expires_at' => now()->addMinute()->timestamp,
                'user_id' => $user->getAuthIdentifier(),
                'tenant_id' => (string) (tenancy()->tenant?->getTenantKey() ?? ''),
            ],
        ])
        ->get('/_test/protected-history')
        ->assertRedirect(route('tenant.dashboard.notes.two-factor.challenge', ['scope' => 'history']))
        ->assertSessionHas('notes_access.return_to', '/_test/protected-history');
});

it('stores historical confirmation separately from discipline confirmation', function (): void {
    Notification::fake();
    $user = notesEmailChallengeUser();

    $this->actingAs($user, 'tenant')
        ->get('/_test/notes-two-factor-challenge?scope=history&return_to=%2F_test%2Fprotected-history');

    $code = notesEmailChallengeCode($user);

    $this->post('/_test/notes-two-factor-challenge', ['code' => $code])
        ->assertRedirect('/_test/protected-history')
        ->assertSessionHas('notes_access.authorizations.history')
        ->assertSessionMissing('notes_access.authorizations.discipline');
});

it('does not reuse discipline authorization for full pauta exports', function (): void {
    $user = notesEmailChallengeUser();

    $this->actingAs($user, 'tenant')
        ->withSession([
            'notes_access.authorizations.discipline' => [
                'expires_at' => now()->addMinute()->timestamp,
                'user_id' => $user->getAuthIdentifier(),
                'tenant_id' => (string) (tenancy()->tenant?->getTenantKey() ?? ''),
            ],
        ])
        ->get('/_test/pauta-export')
        ->assertRedirect(route('tenant.dashboard.notes.two-factor.challenge', ['scope' => 'pauta']))
        ->assertSessionHas('notes_access.return_to', '/_test/pauta-export');
});

it('stores full pauta export confirmation separately', function (): void {
    Notification::fake();
    $user = notesEmailChallengeUser();

    $this->actingAs($user, 'tenant')
        ->get('/_test/notes-two-factor-challenge?scope=pauta&return_to=%2F_test%2Fpauta-export');

    $code = notesEmailChallengeCode($user);

    $this->post('/_test/notes-two-factor-challenge', ['code' => $code])
        ->assertRedirect('/_test/pauta-export')
        ->assertSessionHas('notes_access.authorizations.pauta')
        ->assertSessionMissing('notes_access.authorizations.discipline');
});

it('starts a browser download after confirming a full pauta export', function (): void {
    Notification::fake();
    $user = notesEmailChallengeUser();
    $exportUrl = '/dashboard/pautas/cursos/curso-id/turmas/turma-id/exportar-pauta/excel?periodo=final';

    $this->actingAs($user, 'tenant')
        ->get('/_test/notes-two-factor-challenge?scope=pauta&return_to='.urlencode($exportUrl));

    $code = notesEmailChallengeCode($user);

    $this->withHeader('X-Inertia', 'true')
        ->post('/_test/notes-two-factor-challenge', ['code' => $code])
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', url($exportUrl));
});
