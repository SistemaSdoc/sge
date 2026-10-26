<?php

namespace App\Traits;

use App\Models\Tenant\SugestaoTemaPap;
use App\Models\Tenant\User;
use App\Notifications\CursoTutelado\SugestaoTemaAprovadaNotification;
use App\Notifications\CursoTutelado\SugestaoTemaCriadaNotification;
use App\Notifications\CursoTutelado\SugestaoTemaMelhoriaSolicitadaNotification;
use App\Notifications\CursoTutelado\SugestaoTemaRejeitadaNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

trait NotificaSugestaoTemaPap
{
    private function destinatariosSugestaoTema(SugestaoTemaPap $sugestao): Collection
    {
        $cursoTutelado = $sugestao->cursoTutelado;

        $professoresUsers = $cursoTutelado->professores()
            ->where(function ($q) {
                $q->where('curso_tutelado_professor.coordenador', true)
                    ->orWhereIn('curso_tutelado_professor.grupo_disciplinar', ['membro', 'coordenador']);
            })
            ->get()
            ->map->user
            ->filter();

        $directores = User::whereHas('roles', fn ($q) => $q->where('name', 'Director'))
            ->where('instituicao_id', $cursoTutelado->instituicao_tutora_id)
            ->get();

        return $professoresUsers->merge($directores)->unique('id');
    }

    protected function notificarSugestaoTemaCriada(SugestaoTemaPap $sugestao, User $criadoPor): void
    {
        $destinatarios = $this->destinatariosSugestaoTema($sugestao);
        Notification::send($destinatarios, new SugestaoTemaCriadaNotification($sugestao, $criadoPor));
    }

    protected function notificarSugestaoTemaAprovada(SugestaoTemaPap $sugestao): void
    {
        $destinatarios = $this->destinatariosSugestaoTema($sugestao);

        $criador = $sugestao->criadoPor;
        if ($criador) {
            $destinatarios = $destinatarios->push($criador)->unique('id');
        }

        Notification::send($destinatarios, new SugestaoTemaAprovadaNotification($sugestao));
    }

    protected function notificarSugestaoTemaRejeitada(SugestaoTemaPap $sugestao): void
    {
        $criador = $sugestao->criadoPor;
        if ($criador) {
            $criador->notify(new SugestaoTemaRejeitadaNotification($sugestao));
        }
    }

    protected function notificarSugestaoTemaMelhoriaSolicitada(SugestaoTemaPap $sugestao): void
    {
        $destinatarios = $this->destinatariosSugestaoTema($sugestao);

        $criador = $sugestao->criadoPor;
        if ($criador) {
            $destinatarios = $destinatarios->push($criador)->unique('id');
        }

        Notification::send($destinatarios, new SugestaoTemaMelhoriaSolicitadaNotification($sugestao));
    }
}
