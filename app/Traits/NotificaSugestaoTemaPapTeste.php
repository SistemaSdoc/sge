<?php

namespace App\Traits;

use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\SugestaoTemaPap;
use App\Models\Tenant\User;
use App\Notifications\CursoTutelado\SugestaoTemaPapNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

trait NotificaSugestaoTemaPap
{
    protected function notificarSugestaoTemaCriada(SugestaoTemaPap $sugestao, ?User $actor): void
    {
        $this->notificarAprovadoresDaSugestaoTema($sugestao, SugestaoTemaPapNotification::CRIADA, $actor);
    }

    protected function notificarSugestaoTemaAtualizada(SugestaoTemaPap $sugestao, ?User $actor): void
    {
        $this->notificarAprovadoresDaSugestaoTema($sugestao, SugestaoTemaPapNotification::ATUALIZADA, $actor);
    }

    protected function notificarSugestaoTemaAprovada(SugestaoTemaPap $sugestao, ?User $actor): void
    {
        $this->notificarGrupoDisciplinarDaSugestaoTema($sugestao, SugestaoTemaPapNotification::APROVADA, $actor);
    }

    protected function notificarSugestaoTemaRejeitada(SugestaoTemaPap $sugestao, ?User $actor): void
    {
        $this->notificarGrupoDisciplinarDaSugestaoTema($sugestao, SugestaoTemaPapNotification::REJEITADA, $actor);
    }

    protected function notificarSugestaoTemaMelhoriaSolicitada(SugestaoTemaPap $sugestao, ?User $actor): void
    {
        $this->notificarGrupoDisciplinarDaSugestaoTema($sugestao, SugestaoTemaPapNotification::MELHORIA_SOLICITADA, $actor);
    }

    protected function notificarSugestaoTemaResubmetida(SugestaoTemaPap $sugestao, ?User $actor): void
    {
        $this->notificarAprovadoresDaSugestaoTema($sugestao, SugestaoTemaPapNotification::RESUBMETIDA, $actor);
    }

    private function notificarAprovadoresDaSugestaoTema(
        SugestaoTemaPap $sugestao,
        string $evento,
        ?User $actor,
    ): void {
        $this->notificarTodosEnvolvidosDaSugestao($sugestao, $evento, $actor);
    }

    private function notificarGrupoDisciplinarDaSugestaoTema(
        SugestaoTemaPap $sugestao,
        string $evento,
        ?User $actor,
    ): void {
        $this->notificarTodosEnvolvidosDaSugestao($sugestao, $evento, $actor);
    }

    private function notificarTodosEnvolvidosDaSugestao(
        SugestaoTemaPap $sugestao,
        string $evento,
        ?User $actor,
    ): void {
        $cursoTutelado = $this->cursoTuteladoDaSugestao($sugestao);

        if (! $cursoTutelado) {
            return;
        }

        $instituicaoId = $cursoTutelado->instituicaoCurso?->instituicao_id;
        $aprovadoresInstitucionais = $instituicaoId
            ? User::role(['Director', 'Coordenador'], 'tenant')
                ->where('instituicao_id', $instituicaoId)
                ->get()
            : collect();

        $membrosGrupo = $cursoTutelado->professores()
            ->wherePivotIn('grupo_disciplinar', ['membro', 'coordenador'])
            ->with('user')
            ->get()
            ->map->user
            ->filter();

        $this->enviarNotificacaoSugestaoTema(
            $this->adicionarCriadorAosDestinatarios(
                $aprovadoresInstitucionais->merge($membrosGrupo),
                $sugestao,
            ),
            $sugestao,
            $evento,
            $actor,
        );
    }

    private function adicionarCriadorAosDestinatarios(Collection $destinatarios, SugestaoTemaPap $sugestao): Collection
    {
        if (! $sugestao->relationLoaded('criadoPor')) {
            $sugestao->load('criadoPor');
        }

        $criador = $sugestao->getRelation('criadoPor');

        return $criador
            ? $destinatarios->push($criador)->unique('id')->values()
            : $destinatarios;
    }

    private function cursoTuteladoDaSugestao(SugestaoTemaPap $sugestao): ?CursoTutelado
    {
        $sugestao->loadMissing('cursoTutelado.instituicaoCurso.curso');

        return $sugestao->cursoTutelado;
    }

    private function enviarNotificacaoSugestaoTema(
        Collection $destinatarios,
        SugestaoTemaPap $sugestao,
        string $evento,
        ?User $actor,
    ): void {
        $destinatarios = $destinatarios
            ->reject(fn (User $user): bool => $actor && $user->is($actor))
            ->unique('id')
            ->values();

        if ($destinatarios->isEmpty()) {
            Log::warning('Sugestão de tema sem destinatários para notificação.', [
                'sugestao_tema_id' => $sugestao->getKey(),
                'curso_tutelado_id' => $sugestao->curso_tutelado_id,
                'evento' => $evento,
                'actor_id' => $actor?->getKey(),
            ]);

            return;
        }

        Notification::sendNow(
            $destinatarios,
            new SugestaoTemaPapNotification($sugestao, $evento, ['database']),
            ['database'],
        );

        Notification::send(
            $destinatarios,
            new SugestaoTemaPapNotification($sugestao, $evento, ['mail']),
        );
    }
}
