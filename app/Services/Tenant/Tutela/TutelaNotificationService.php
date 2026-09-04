<?php

namespace App\Services\Tenant\Tutela;

use App\Enums\TutelaStatus;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\User;
use App\Notifications\ConversaoTutelaPropriaNotification;
use App\Notifications\ConversaoTutelaPropriaResultadoNotification;
use App\Notifications\SolicitacaoTutelaNotification;
use App\Notifications\TrocaTutelaNotification;
use App\Notifications\TrocaTutelaRejeitadaNotification;
use App\Notifications\TrocaTutelaResultadoNotification;
use App\Services\Central\TenantService;
use Illuminate\Support\Facades\Log;

/**
 * Envia notificações de tutela no tenant tutor.
 */
class TutelaNotificationService
{
    /**
     * Recebe o serviço usado para localizar instituições noutros tenants.
     */
    public function __construct(private readonly TenantService $tenantService) {}

    /**
     * Envia a notificação ao administrador do tenant tutor.
     *
     * A procura do administrador e a gravação da notificação acontecem dentro
     * do contexto do tenant tutor.
     */
    public function notificarNovaSolicitacao(CursoTuteladoShared $shared): void
    {
        Log::debug('Iniciando notificação de nova solicitação de tutela', [
            'shared_id' => $shared->id,
            'tenant_tutor_id' => $shared->tenant_tutor_id,
            'tenant_tutelado_id' => $shared->tenant_tutelado_id,
            'curso_nome' => $shared->curso_nome,
        ]);

        $tenantTutor = Tenant::query()->find($shared->tenant_tutor_id);
        $tenantTutelado = Tenant::query()->find($shared->tenant_tutelado_id);

        if (! $tenantTutor || ! $tenantTutelado || ! $tenantTutor->admin_user_id) {
            Log::warning('Notificação não enviada: dados incompletos', [
                'shared_id' => $shared->id,
                'tenant_tutor_existe' => (bool) $tenantTutor,
                'tenant_tutelado_existe' => (bool) $tenantTutelado,
                'admin_user_id' => $tenantTutor?->admin_user_id,
            ]);

            return;
        }

        $instituicaoTutelada = $this->tenantService->getInstituicao($tenantTutelado);

        if (! $instituicaoTutelada) {
            Log::warning('Notificação não enviada: instituição tutelada não encontrada', [
                'shared_id' => $shared->id,
                'tenant_tutelado_id' => $shared->tenant_tutelado_id,
            ]);

            return;
        }

        $tenantTutor->run(function () use (
            $tenantTutor,
            $instituicaoTutelada,
            $shared
        ): void {
            $admin = User::query()->find($tenantTutor->admin_user_id);

            if (! $admin) {
                Log::warning('Notificação não enviada: admin user não encontrado', [
                    'shared_id' => $shared->id,
                    'tenant_tutor_id' => $tenantTutor->id,
                    'admin_user_id' => $tenantTutor->admin_user_id,
                ]);

                return;
            }

            Log::info('Enviando notificação de solicitação de tutela', [
                'shared_id' => $shared->id,
                'tenant_tutor_id' => $tenantTutor->id,
                'admin_email' => $admin->email,
                'instituicao_tutelada' => $instituicaoTutelada->nome,
                'curso_nome' => $shared->curso_nome,
            ]);

            $admin->notify(new SolicitacaoTutelaNotification(
                instituicaoTutelada: $instituicaoTutelada->nome,
                cursoNome: $shared->curso_nome,
                sharedId: (string) $shared->getKey(),
                url: $this->url($tenantTutor, (string) $shared->getKey()),
            ));

            Log::info('Notificação de solicitação de tutela enviada com sucesso', [
                'shared_id' => $shared->id,
                'admin_email' => $admin->email,
            ]);
        });
    }

    public function notificarTrocaTutela(
        CursoTutelado $cursoTutelado,
        string $tenantTutorAnteriorId,
        ?string $cursoTuteladoSharedAnteriorId = null,
        ?CursoTuteladoShared $shared = null,
    ): void {
        $shared ??= $cursoTutelado->cursoTuteladoShared;

        if (! $shared || ! $cursoTutelado->instituicaoCurso || ! $cursoTutelado->instituicaoCurso->instituicao) {
            Log::warning('Notificação de troca de tutela não enviada: dados incompletos', [
                'curso_tutelado_id' => $cursoTutelado->id,
                'shared_id' => $shared?->id,
            ]);

            return;
        }

        $tenantAnterior = Tenant::query()->find($tenantTutorAnteriorId);
        $tenantAtual = Tenant::query()->find($shared->tenant_tutor_id);
        $tenantTutelado = Tenant::query()->find($shared->tenant_tutelado_id);

        if (! $tenantAnterior || ! $tenantAtual || ! $tenantTutelado) {
            Log::warning('Notificação de troca de tutela não enviada: tenants inválidos', [
                'tenant_anterior_id' => $tenantTutorAnteriorId,
                'tenant_atual_id' => $shared->tenant_tutor_id,
                'tenant_tutelado_id' => $shared->tenant_tutelado_id,
            ]);

            return;
        }

        $instituicaoTutelada = $this->tenantService->getInstituicao($tenantTutelado);
        $instituicaoNova = $this->tenantService->getInstituicao($tenantAtual);

        if (! $instituicaoTutelada || ! $instituicaoNova) {
            Log::warning('Notificação de troca de tutela não enviada: instituições inválidas', [
                'tenant_anterior_id' => $tenantTutorAnteriorId,
                'tenant_atual_id' => $shared->tenant_tutor_id,
                'tenant_tutelado_id' => $shared->tenant_tutelado_id,
            ]);

            return;
        }

        $tenantAnterior->run(function () use ($tenantAnterior, $instituicaoNova, $instituicaoTutelada, $shared, $cursoTuteladoSharedAnteriorId): void {
            $admin = User::query()->find($tenantAnterior->admin_user_id);

            if (! $admin) {
                Log::warning('Notificação de troca de tutela não enviada: admin anterior não encontrado', [
                    'tenant_id' => $tenantAnterior->id,
                    'shared_id' => $shared->id,
                ]);

                return;
            }

            $admin->notify(new TrocaTutelaNotification(
                instituicaoNova: $instituicaoNova->nome,
                instituicaoTutelada: $instituicaoTutelada->nome,
                cursoNome: $shared->curso_nome,
                sharedId: (string) $shared->getKey(),
                tenantTutorAnteriorId: (string) $tenantAnterior->getKey(),
                cursoTuteladoSharedAnteriorId: $cursoTuteladoSharedAnteriorId,
                url: $this->url($tenantAnterior, (string) $shared->getKey()),
            ));
        });
    }

    public function aprovarTrocaTutela(CursoTuteladoShared $shared): void
    {
        $tenantAtual = Tenant::query()->find($shared->tenant_tutor_id);
        $sharedAnterior = CursoTuteladoShared::on(
            config('tenancy.database.central_connection', config('database.default'))
        )
            ->where('curso_tutelado_tutelado_id', $shared->curso_tutelado_tutelado_id)
            ->where('status', TutelaStatus::ACTIVO)
            ->first();

        if (! $tenantAtual || ! $tenantAtual->admin_user_id) {
            return;
        }

        $tenantAtual->run(function () use ($shared, $tenantAtual, $sharedAnterior): void {
            $admin = User::query()->find($tenantAtual->admin_user_id);

            if (! $admin) {
                return;
            }

            $admin->notify(new SolicitacaoTutelaNotification(
                instituicaoTutelada: $shared->tenant_tutelado_id ? $this->tenantService->getInstituicao(Tenant::query()->findOrFail($shared->tenant_tutelado_id))->nome : 'Instituição tutelada',
                cursoNome: $shared->curso_nome,
                sharedId: (string) $shared->getKey(),
                url: $this->url($tenantAtual, (string) $shared->getKey()),
                trocaTutelaFinal: true,
                cursoTuteladoSharedAnteriorId: $sharedAnterior?->getKey(),
            ));
        });
    }

    public function notificarRejeicaoTroca(
        CursoTuteladoShared $shared,
        string $tenantTutorAnteriorId,
    ): void {
        $tenantTutelado = Tenant::query()->find($shared->tenant_tutelado_id);
        $tenantAnterior = Tenant::query()->find($tenantTutorAnteriorId);
        $tenantAtual = Tenant::query()->find($shared->tenant_tutor_id);

        if (! $tenantTutelado || ! $tenantAnterior || ! $tenantAtual || ! $tenantTutelado->admin_user_id) {
            Log::warning('Notificação de rejeição de troca não enviada: dados incompletos', [
                'shared_id' => $shared->id,
                'tenant_tutelado_id' => $shared->tenant_tutelado_id,
                'tenant_anterior_id' => $tenantTutorAnteriorId,
                'tenant_atual_id' => $shared->tenant_tutor_id,
            ]);

            return;
        }

        $instituicaoAnterior = $this->tenantService->getInstituicao($tenantAnterior);
        $instituicaoAtual = $this->tenantService->getInstituicao($tenantAtual);

        if (! $instituicaoAnterior || ! $instituicaoAtual) {
            return;
        }

        $tenantTutelado->run(function () use ($tenantTutelado, $instituicaoAnterior, $instituicaoAtual, $shared): void {
            $admin = User::query()->find($tenantTutelado->admin_user_id);

            if (! $admin) {
                return;
            }

            $admin->notify(new TrocaTutelaRejeitadaNotification(
                instituicaoRejeitou: $instituicaoAnterior->nome,
                instituicaoProposta: $instituicaoAtual->nome,
                cursoNome: $shared->curso_nome,
                sharedId: (string) $shared->getKey(),
                url: $this->url($tenantTutelado, (string) $shared->getKey()),
            ));
        });
    }

    public function notificarResultadoTroca(
        CursoTuteladoShared $shared,
        string $tenantDecisorId,
        string $resultado,
        string $fase,
    ): void {
        $tenantTutelado = Tenant::query()->find($shared->tenant_tutelado_id);
        $tenantDecisor = Tenant::query()->find($tenantDecisorId);
        $tenantProposta = Tenant::query()->find($shared->tenant_tutor_id);

        if (! $tenantTutelado || ! $tenantDecisor || ! $tenantProposta || ! $tenantTutelado->admin_user_id) {
            Log::warning('Notificação de resultado da troca não enviada: dados incompletos', [
                'shared_id' => $shared->id,
                'tenant_tutelado_id' => $shared->tenant_tutelado_id,
                'tenant_decisor_id' => $tenantDecisorId,
                'tenant_proposta_id' => $shared->tenant_tutor_id,
            ]);

            return;
        }

        $instituicaoDecisora = $this->tenantService->getInstituicao($tenantDecisor);
        $instituicaoProposta = $this->tenantService->getInstituicao($tenantProposta);

        if (! $instituicaoDecisora || ! $instituicaoProposta) {
            return;
        }

        $tenantTutelado->run(function () use ($tenantTutelado, $instituicaoDecisora, $instituicaoProposta, $shared, $resultado, $fase): void {
            $admin = User::query()->find($tenantTutelado->admin_user_id);

            if (! $admin) {
                return;
            }

            $admin->notify(new TrocaTutelaResultadoNotification(
                instituicaoDecisora: $instituicaoDecisora->nome,
                instituicaoProposta: $instituicaoProposta->nome,
                cursoNome: $shared->curso_nome,
                sharedId: (string) $shared->getKey(),
                resultado: $resultado,
                fase: $fase,
                url: $this->url($tenantTutelado, (string) $shared->getKey()),
            ));
        });
    }

    public function notificarTrocaPendente(
        CursoTuteladoShared $shared,
        string $tenantTutorActualId,
    ): void {
        $this->notificarResultadoTroca(
            $shared,
            $tenantTutorActualId,
            'pendente',
            'instituicao_anterior',
        );
    }

    public function notificarConversaoTutelaPropria(
        CursoTutelado $cursoTutelado,
        string $tenantTutorAnteriorId,
        string $sharedId,
    ): void {
        $tenantTutor = Tenant::query()->find($tenantTutorAnteriorId);
        $tenantTutelado = Tenant::query()->find(tenancy()->tenant->getTenantKey());
        $shared = CursoTuteladoShared::on(
            config('tenancy.database.central_connection', config('database.default'))
        )->find($sharedId);

        if (! $tenantTutor || ! $tenantTutelado || ! $shared || ! $tenantTutor->admin_user_id) {
            return;
        }

        $instituicaoSolicitante = $this->tenantService->getInstituicao($tenantTutelado);
        $instituicaoActual = $this->tenantService->getInstituicao($tenantTutor);

        if (! $instituicaoSolicitante || ! $instituicaoActual) {
            return;
        }

        $tenantTutor->run(function () use ($tenantTutor, $instituicaoSolicitante, $instituicaoActual, $shared): void {
            $admin = User::query()->find($tenantTutor->admin_user_id);

            if ($admin) {
                $admin->notify(new ConversaoTutelaPropriaNotification(
                    instituicaoSolicitante: $instituicaoSolicitante->nome,
                    instituicaoActual: $instituicaoActual->nome,
                    cursoNome: $shared->curso_nome,
                    sharedId: (string) $shared->getKey(),
                    tenantTutorAnteriorId: (string) $tenantTutor->getTenantKey(),
                    url: $this->url($tenantTutor, (string) $shared->getKey()),
                ));
            }
        });
    }

    public function notificarResultadoConversaoTutelaPropria(
        CursoTuteladoShared $shared,
        string $tenantDecisorId,
        string $resultado,
    ): void {
        $tenantTutelado = Tenant::query()->find($shared->tenant_tutelado_id);
        $tenantDecisor = Tenant::query()->find($tenantDecisorId);

        if (! $tenantTutelado || ! $tenantDecisor || ! $tenantTutelado->admin_user_id) {
            return;
        }

        $instituicaoDecisora = $this->tenantService->getInstituicao($tenantDecisor);

        if (! $instituicaoDecisora) {
            return;
        }

        $tenantTutelado->run(function () use ($tenantTutelado, $instituicaoDecisora, $shared, $resultado): void {
            $admin = User::query()->find($tenantTutelado->admin_user_id);

            if ($admin) {
                $admin->notify(new ConversaoTutelaPropriaResultadoNotification(
                    instituicaoDecisora: $instituicaoDecisora->nome,
                    cursoNome: $shared->curso_nome,
                    sharedId: (string) $shared->getKey(),
                    resultado: $resultado,
                    url: $this->url($tenantTutelado, (string) $shared->getKey()),
                ));
            }
        });
    }

    /**
     * Constrói o URL absoluto da notificação no domínio do tenant tutor.
     */
    private function url(Tenant $tenant, string $sharedId): string
    {
        $domain = $tenant->domains()->first()?->domain;
        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'http';

        return $domain
            ? "{$scheme}://{$domain}/dashboard/notificacoes/tutela/{$sharedId}"
            : url("/dashboard/notificacoes/tutela/{$sharedId}");
    }
}
