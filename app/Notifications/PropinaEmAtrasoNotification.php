<?php

namespace App\Notifications;

use App\Models\Tenant\Aluno;
use App\Models\Tenant\Instituicao;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PropinaEmAtrasoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  int  $totalMeses  Número total de meses em atraso
     * @param  float  $valorTotal  Valor total da dívida (incluindo multas)
     * @param  array  $meses  Lista de meses em atraso (ex: ['Agosto/2026'])
     * @param  string  $assinatura  Hash para evitar duplicação
     * @param  string|null  $alunoId  ID do aluno para link direto
     * @param  array|null  $pendencias  Detalhes de cada pendência (para exibir base + multa)
     * @param  Instituicao|null  $instituicao  Instituição (opcional, para personalizar)
     */
    public function __construct(
        public int $totalMeses,
        public float $valorTotal,
        public array $meses,
        public string $assinatura,
        public ?string $alunoId = null,
        public ?array $pendencias = null,
        public ?Instituicao $instituicao = null,
    ) {}

    public function via($notifiable): array
    {
        $canais = ['database'];

        if ($notifiable->email && filter_var($notifiable->email, FILTER_VALIDATE_EMAIL)) {
            $canais[] = 'mail';
        }

        Log::debug('[Notificação] Canais definidos', [
            'user_id' => $notifiable->id ?? null,
            'canais' => $canais,
        ]);

        return $canais;
    }

    public function toDatabase($notifiable): array
    {
        return [
            'tipo' => 'propina_atraso',
            'titulo' => "Propina em atraso ({$this->totalMeses} mês(es))",
            'mensagem' => "Você tem {$this->totalMeses} mês(es) de propina em atraso, totalizando ".$this->formatarMoeda($this->valorTotal).' AOA.',
            'meses' => $this->meses,
            'valor_total' => $this->valorTotal,
            'assinatura' => $this->assinatura,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        Log::debug('[Notificação] toMail - preparando e-mail detalhado', [
            'user_id' => $notifiable->id ?? null,
            'email' => $notifiable->email ?? null,
        ]);

        // Obter o aluno e dados da instituição
        $aluno = Aluno::with([
            'turmaActual.cursoClasseTurno.cursoClasse.classe',
            'turmaActual.cursoClasseTurno.turno',
            'turmaActual.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
        ])->find($this->alunoId);

        $turma = $aluno?->turmaActual->first();
        $curso = $turma?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso;
        $classe = $turma?->cursoClasseTurno?->cursoClasse?->classe;
        $turno = $turma?->cursoClasseTurno?->turno;

        // Instituição (se não passada, tenta buscar da relação)
        $instituicao = $this->instituicao ?? ($aluno?->user?->instituicao ?? null);
        $instituicaoNome = $instituicao?->nome ?? config('app.name');
        $instituicaoLogotipo = $instituicao?->logo ? Storage::url($instituicao->logo) : null;

        // Monta a lista de meses com valores (base + multa) se disponível
        $linhasPendencias = [];
        if ($this->pendencias) {
            foreach ($this->pendencias as $p) {
                $linhasPendencias[] = [
                    'mes' => $p['mes'].'/'.$p['ano'],
                    'base' => $p['valor_base'],
                    'multa' => $p['multa'],
                    'total' => $p['valor'],
                ];
            }
        }

        $valorBaseTotal = collect($this->pendencias)->sum('valor_base') ?? $this->valorTotal;
        $multaTotal = collect($this->pendencias)->sum('multa') ?? 0;

        // Construir a mensagem de e-mail com HTML personalizado
        $mail = (new MailMessage)
            ->subject(' Propina em atraso - '.$instituicaoNome)
            ->greeting('Olá, '.($notifiable->nome ?? 'Usuário'))
            ->line('A sua situação financeira na instituição necessita de atenção.');

        // Adicionar conteúdo HTML enriquecido
        $mail->view('emails.propina-em-atraso', [
            'instituicaoNome' => $instituicaoNome,
            'instituicaoLogotipo' => $instituicaoLogotipo,
            'alunoNome' => $aluno?->user?->nome ?? 'Aluno',
            'curso' => $curso?->nome ?? 'Curso não informado',
            'classe' => $classe?->nome ?? 'Classe não informada',
            'turmaNome' => $turma?->nome ?? 'Turma não informada',
            'turno' => $turno?->nome ?? 'Turno não informado',
            'totalMeses' => $this->totalMeses,
            'meses' => $this->meses,
            'linhasPendencias' => $linhasPendencias,
            'valorBaseTotal' => $valorBaseTotal,
            'multaTotal' => $multaTotal,
            'valorTotal' => $this->valorTotal,
            'urlPagamento' => $this->alunoId
                ? route('pagamentos.create', ['aluno_id' => $this->alunoId])
                : route('pagamentos.index'),
        ]);

        return $mail;
    }

    /**
     * Formata moeda.
     */
    private function formatarMoeda(float $valor): string
    {
        return number_format($valor, 2, ',', '.');
    }
}
