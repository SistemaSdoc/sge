<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PerfilIncompletoNotificacao extends Notification
{
    use Queueable;

    /**
     * Email + base de dados.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Email com a lista de campos obrigatórios.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Ação necessária: complete o seu perfil')
            ->greeting("Olá, {$notifiable->nome}")
            ->line('Bem-vindo(a) ao **SGE — Sistema de Gestão Escolar**.')
            ->line('')
            ->line('A sua conta foi criada com sucesso, mas **o seu perfil ainda está incompleto**.')
            ->line('')
            ->line('**Para poder aceder ao sistema, é obrigatório preencher os seguintes dados:**')
            ->line('')
            ->line('• **Nome completo do pai**')
            ->line('• **Nome completo da mãe**')
            ->line('• **Data de nascimento**')
            ->line('• **Género**')
            ->line('• **Nacionalidade**')
            ->line('• **Naturalidade**')
            ->line('• **Município**')
            ->line('• **Telefone**')
            ->line('')
            ->line('Enquanto estes campos não estiverem preenchidos, **não conseguirá aceder ao painel nem às suas notas, pautas ou documentos**.')
            ->line('')
            ->line('Leva menos de 2 minutos. Clique no botão abaixo para começar:')
            ->action('Completar perfil agora', url('/dashboard/settings/profile'))
            ->line('')
            ->line('Se tiver dificuldades, contacte a secretaria da sua instituição.')
            ->salutation('Obrigado,
A equipa SGE');
    }

    /**
     * Notificação no sino.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tipo'     => 'perfil_incompleto',
            'titulo'   => 'Complete o seu perfil',
            'mensagem' => 'O seu acesso está limitado. Preencha os dados obrigatórios para continuar.',
            'url'      => '/dashboard/settings/profile',
        ];
    }
}