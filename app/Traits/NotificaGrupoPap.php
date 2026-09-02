<?php

namespace App\Traits;

use App\Models\Tenant\ElementoGrupoPap;
use App\Models\Tenant\GrupoPap;
use App\Notifications\Pap\CorrecaoSolicitadaNotification;
use App\Notifications\Pap\DataDefesaDefinidaNotification;
use App\Notifications\Pap\MelhoriasSolicitadasNotification;
use App\Notifications\Pap\NotaAtribuidaNotification;
use App\Notifications\Pap\TemaAprovadoNotification;
use App\Notifications\Pap\TemaReprovadoNotification;
use App\Notifications\Pap\TemaSubmetidoAoTutorNotification;
use App\Notifications\Pap\TemaValidadoPeloTutorNotification;
use App\Notifications\Pap\TrabalhoAprovadoNotification;
use App\Notifications\Pap\TrabalhoSubmetidoNotification;
use Illuminate\Support\Facades\Notification;

trait NotificaGrupoPap
{
    protected function notificarTemaSubmetidoAoTutor(GrupoPap $grupoPap): void
    {
        $tutor = $grupoPap->professor?->user;
        if ($tutor) {
            $tutor->notify(new TemaSubmetidoAoTutorNotification($grupoPap));
        }
    }

    protected function notificarTemaValidadoPeloTutor(GrupoPap $grupoPap, $coordenadores): void
    {
        Notification::send($coordenadores, new TemaValidadoPeloTutorNotification($grupoPap));
    }

    protected function notificarTemaAprovado(GrupoPap $grupoPap): void
    {
        $utilizadores = $grupoPap->alunos->map->user->filter();

        // Tutor também recebe quando o tema é aprovado pela coordenação
        $tutor = $grupoPap->professor?->user;
        if ($tutor) {
            $utilizadores = $utilizadores->push($tutor)->unique('id');
        }

        Notification::send($utilizadores, new TemaAprovadoNotification($grupoPap));
    }

    protected function notificarTemaReprovado(GrupoPap $grupoPap): void
    {
        $utilizadores = $grupoPap->alunos->map->user->filter();
        Notification::send($utilizadores, new TemaReprovadoNotification($grupoPap));
    }

    protected function notificarMelhoriasSolicitadas(GrupoPap $grupoPap, string $solicitadoPor): void
    {
        $utilizadores = $grupoPap->alunos->map->user->filter();

        if ($solicitadoPor === 'coordenacao') {
            $tutor = $grupoPap->professor?->user;
            if ($tutor) {
                $utilizadores = $utilizadores->push($tutor)->unique('id');
            }
        }

        Notification::send($utilizadores, new MelhoriasSolicitadasNotification($grupoPap, $solicitadoPor));
    }

    protected function notificarDataDefesaDefinida(GrupoPap $grupoPap): void
    {
        $alunosUsers = $grupoPap->alunos->map->user->filter();
        $juradosUsers = $grupoPap->jurados->map->professor->map->user->filter();

        $tutor = $grupoPap->professor?->user;
        $destinatarios = $alunosUsers->merge($juradosUsers)->unique('id');

        if ($tutor) {
            $destinatarios = $destinatarios->push($tutor)->unique('id');
        }

        Notification::send($destinatarios, new DataDefesaDefinidaNotification($grupoPap));
    }

    protected function notificarTrabalhoSubmetido(GrupoPap $grupoPap, $revisores): void
    {
        Notification::send($revisores, new TrabalhoSubmetidoNotification($grupoPap));
    }

    protected function notificarCorrecaoSolicitada(GrupoPap $grupoPap, string $comentario, string $solicitadoPor = 'tutor'): void
    {
        $utilizadores = $grupoPap->alunos->map->user->filter();
        Notification::send($utilizadores, new CorrecaoSolicitadaNotification($grupoPap, $comentario, $solicitadoPor));
    }

    protected function notificarTrabalhoAprovado(GrupoPap $grupoPap): void
    {
        $utilizadores = $grupoPap->alunos->map->user->filter();

        $tutor = $grupoPap->professor?->user;
        if ($tutor) {
            $utilizadores = $utilizadores->push($tutor)->unique('id');
        }

        Notification::send($utilizadores, new TrabalhoAprovadoNotification($grupoPap));
    }

    protected function notificarNotaAtribuida(GrupoPap $grupoPap, ElementoGrupoPap $elemento): void
    {
        $aluno = $elemento->aluno?->user;
        if ($aluno) {
            $aluno->notify(new NotaAtribuidaNotification($grupoPap, $elemento));
        }
    }
}
