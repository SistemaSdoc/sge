<?php

namespace App\Notifications\CursoTutelado;

use App\Models\Tenant\SugestaoTemaPap;
use App\Notifications\Concerns\ReliableNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SugestaoTemaPapNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;
    use ReliableNotification;

    public const string CRIADA = 'criada';

    public const string ATUALIZADA = 'atualizada';

    public const string APROVADA = 'aprovada';

    public const string REJEITADA = 'rejeitada';

    public const string MELHORIA_SOLICITADA = 'melhoria_solicitada';

    public const string RESUBMETIDA = 'resubmetida';

    public function __construct(
        public SugestaoTemaPap $sugestao,
        public string $evento,
        public array $channels = ['database', 'mail'],
    ) {}

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $conteudo = $this->conteudo();

        return (new MailMessage)
            ->subject($conteudo['titulo'])
            ->view('mail.curso-tutelado.sugestao-tema', [
                ...$conteudo,
                'nomeCurso' => $this->nomeCurso(),
                'tituloSugestao' => $this->sugestao->titulo,
                'aprovador' => $this->nomeAprovador(),
                'observacao' => $this->sugestao->observacao_aprovacao,
                'url' => $this->urlCurso(),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $conteudo = $this->conteudo();

        return [
            'tipo' => 'sugestao_tema_'.$this->evento,
            'titulo' => $conteudo['titulo'],
            'mensagem' => $conteudo['mensagem'],
            'curso_tutelado_id' => $this->sugestao->curso_tutelado_id,
            'sugestao_tema_id' => $this->sugestao->getKey(),
            'aprovado_por' => in_array($this->evento, [self::APROVADA, self::REJEITADA, self::MELHORIA_SOLICITADA], true)
                ? $this->nomeAprovador()
                : null,
            'observacao' => $this->sugestao->observacao_aprovacao,
            'url' => $this->urlCurso(),
        ];
    }

    private function conteudo(): array
    {
        $titulo = $this->sugestao->titulo;
        $nomeCurso = $this->nomeCurso();
        $aprovador = $this->nomeAprovador();

        return match ($this->evento) {
            self::CRIADA => [
                'titulo' => 'Nova sugestão de tema PAP',
                'mensagem' => "Foi registada uma nova sugestão de tema para o curso \"{$nomeCurso}\".",
            ],
            self::ATUALIZADA => [
                'titulo' => 'Sugestão de tema atualizada',
                'mensagem' => "A sugestão \"{$titulo}\" do curso \"{$nomeCurso}\" foi atualizada.",
            ],
            self::APROVADA => [
                'titulo' => 'Sugestão de tema aprovada',
                'mensagem' => "A sugestão \"{$titulo}\" do curso \"{$nomeCurso}\" foi aprovada por {$aprovador}.",
            ],
            self::REJEITADA => [
                'titulo' => 'Sugestão de tema rejeitada',
                'mensagem' => "A sugestão \"{$titulo}\" do curso \"{$nomeCurso}\" foi rejeitada por {$aprovador}.",
            ],
            self::MELHORIA_SOLICITADA => [
                'titulo' => 'Melhoria solicitada para tema PAP',
                'mensagem' => "Foi solicitada uma melhoria para a sugestão \"{$titulo}\" do curso \"{$nomeCurso}\" por {$aprovador}.",
            ],
            self::RESUBMETIDA => [
                'titulo' => 'Sugestão de tema reenviada',
                'mensagem' => "A sugestão \"{$titulo}\" do curso \"{$nomeCurso}\" foi reenviada para aprovação.",
            ],
        };
    }

    private function nomeAprovador(): string
    {
        if ($this->sugestao->relationLoaded('aprovadoPor')) {
            return $this->sugestao->getRelation('aprovadoPor')?->nome ?? 'a coordenação';
        }

        if (! $this->sugestao->getAttribute('aprovado_por')) {
            return 'a coordenação';
        }

        try {
            $this->sugestao->load('aprovadoPor');
        } catch (\Throwable) {
            return 'a coordenação';
        }

        return $this->sugestao->aprovadoPor?->nome ?? 'a coordenação';
    }

    private function nomeCurso(): string
    {
        return $this->sugestao->cursoTutelado?->instituicaoCurso?->curso?->nome
            ?? 'curso tutelado';
    }

    private function urlCurso(): string
    {
        $cursoTutelado = $this->sugestao->cursoTutelado;

        return route('tenant.dashboard.instituicoes.cursos-tutelados.show', [
            'instituicao' => $cursoTutelado->instituicaoCurso?->instituicao_id,
            'cursoTutelado' => $cursoTutelado->getKey(),
        ]);
    }
}
