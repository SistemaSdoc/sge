<?php

namespace App\Notifications\Channels;

use App\Models\Tenant\Instituicao;
use App\Services\Tenant\InstitutionMailLogo;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Database\QueryException;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class InstitutionLogoMailChannel extends MailChannel
{
    private const LOGO_CONTENT_ID = 'institution-logo@pge.edu.ao';

    public function __construct(
        MailFactory $mailer,
        Markdown $markdown,
        private InstitutionMailLogo $institutionMailLogo
    ) {
        parent::__construct($mailer, $markdown);
    }

    public function send($notifiable, Notification $notification)
    {
        if (! method_exists($notification, 'toMail')) {
            return null;
        }

        $message = $notification->toMail($notifiable);

        if ($message instanceof Mailable) {
            return $message->send($this->mailer);
        }

        if (! $notifiable->routeNotificationFor('mail', $notification)) {
            return null;
        }

        if ($message instanceof MailMessage) {
            $instituicao = $message->viewData['instituicao'] ?? null;

            $tenant = function_exists('tenant') ? tenant() : null;

            if (! $instituicao instanceof Instituicao && $tenant?->instituicao_id) {
                try {
                    $instituicao = $tenant->run(
                        fn (): ?Instituicao => Instituicao::query()->find($tenant->instituicao_id)
                    );
                } catch (QueryException $exception) {
                    report($exception);
                }
            }

            $logo = $this->institutionMailLogo->image(
                $instituicao instanceof Instituicao ? $instituicao : null
            );

            if ($logo !== null) {
                try {
                    $message->viewData['institutionLogoCid'] = 'cid:'.self::LOGO_CONTENT_ID;
                    $message->viewData['institutionLogoAlt'] = $instituicao?->nome ?? config('app.name');
                    $message->withSymfonyMessage(function (Email $mailMessage) use ($logo): void {
                        $contents = $logo['contents'] ?? null;

                        if (! is_string($contents) || $contents === '') {
                            return;
                        }

                        $part = (new DataPart($contents, $logo['name'] ?? 'logo', $logo['mime'] ?? 'image/png'))
                            ->asInline()
                            ->setContentId(self::LOGO_CONTENT_ID);

                        $mailMessage->addPart($part);
                    });
                } catch (\Throwable $exception) {
                    report($exception);
                    unset($message->viewData['institutionLogoCid'], $message->viewData['institutionLogoAlt']);
                }
            }
        }

        return $this->mailer->mailer($message->mailer ?? null)->send(
            $this->buildView($message),
            array_merge($message->data(), $this->additionalMessageData($notification)),
            $this->messageBuilder($notifiable, $notification, $message)
        );
    }
}
