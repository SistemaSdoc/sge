<?php

namespace App\Notifications\Aluno;

use App\Models\Tenant\Aluno;
use App\Models\Tenant\Turma;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MatriculaConfirmadaNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Aluno $aluno,
        public Turma $turmaNova,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $instituicao = $this->aluno->instituicao;

        $nomeAluno = $this->aluno->inscricao?->candidato?->nome
            ?? $this->aluno->user?->nome
            ?? 'Aluno';

        // Carregar relações necessárias se ainda não estiverem carregadas
        $this->turmaNova->loadMissing([
            'anoLectivo',
            'cursoClasseTurno.turno',
            'cursoClasseTurno.cursoClasse.classe',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
        ]);

        $cct = $this->turmaNova->cursoClasseTurno;
        $curso = $cct?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome;
        $classe = $cct?->cursoClasse?->classe?->nome;
        $turno = $cct?->turno?->nome;

        return (new MailMessage)
            ->subject('Confirmação de matrícula')
            ->view('mail.aluno.matricula-confirmada', [
                'nome' => $nomeAluno,
                'curso' => $curso,
                'classe' => $classe,
                'turno' => $turno,
                'nomeTurma' => $this->turmaNova->nome,
                'anoLectivo' => $this->turmaNova->anoLectivo?->nome,
                'instituicao' => $instituicao,
                'artigoInstituicao' => match ($instituicao?->tipo ?? 'instituto') {
                    'instituto', 'colegio' => 'ao',
                    default => 'à',
                },
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $cct = $this->turmaNova->cursoClasseTurno;
        $classe = $cct?->cursoClasse?->classe?->nome;
        $turno = $cct?->turno?->nome;

        return [
            'tipo' => 'matricula_confirmada',
            'titulo' => 'Matrícula confirmada',
            'mensagem' => "A sua matrícula foi confirmada com sucesso para a {$classe}, turma {$this->turmaNova->nome}, turno {$turno}.",
        ];
    }
}
