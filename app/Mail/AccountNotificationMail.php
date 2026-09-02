<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $userName,
        public readonly string $userEmail,
        public readonly string $featureName = 'Iniciar sessão',
        public readonly string $actionAt = '',
        public readonly string $ctaUrl = '',
        public readonly string $ctaLabel = 'Aceder à sua Conta',
        public readonly string $privacyUrl = '',
        public readonly string $unsubscribeUrl = '',
        public readonly array $extraFields = [],
        public readonly string $logoUrl = '',
        public readonly string $illustrationUrl = '',
        public readonly string $companyAddress = '',
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Acompanhe os dados da sua Conta ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.google-account-notification',  // era emails.account-notification
        );
    }

    /**
     * Uso:
     *
     *   Mail::to($user->email)->send(new AccountNotificationMail(
     *       userName:      $user->name,
     *       userEmail:     $user->email,
     *       featureName:   'Iniciar sessão com Google',
     *       actionAt:      now()->toIso8601String(),
     *       ctaUrl:        route('account.settings'),
     *       ctaLabel:      'Aceder à sua Conta',
     *       privacyUrl:    route('privacy'),
     *       unsubscribeUrl: route('unsubscribe', $user->unsubscribe_token),
     *   ));
     */
}