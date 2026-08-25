<?php

namespace App\Jobs;

use App\Models\Central\PendingTenantData;
use App\Models\Central\Tenant;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\User;
use App\Notifications\TenantActivadoNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class CreateTenantInstitution implements ShouldQueue
{
    use Queueable;

    public function __construct(private Tenant $tenant) {}

    /**
     * Executa a criação da instituição e utilizador do tenant.
     */
    public function handle(): void
    {
        try {
            Log::info("CreateTenantInstitution iniciado para tenant: {$this->tenant->id}");

            $pending = PendingTenantData::where('tenant_id', $this->tenant->id)->first();

            if (! $pending) {
                Log::info("Sem dados pendentes para tenant: {$this->tenant->id}");

                return;
            }

            $this->createInstitutionAndUser($pending);

            Log::info("CreateTenantInstitution concluído para tenant: {$this->tenant->id}");
        } catch (\Throwable $e) {
            Log::error("Erro em CreateTenantInstitution: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Cria a instituição e o utilizador admin do tenant.
     */
    private function createInstitutionAndUser(PendingTenantData $pending): void
    {
        DB::transaction(function () use ($pending) {
            $this->tenant->run(function () use ($pending) {
                Log::info("A criar instituição para tenant: {$this->tenant->id}");

                $instituicao = Instituicao::firstOrCreate(
                    ['tenant_id' => $this->tenant->id],
                    [
                        'nome' => $pending->nome,
                        'sigla' => $pending->sigla,
                        'tipo' => $pending->tipo,
                        'status' => $pending->status,
                    ],
                );

                Log::info("Instituição: {$instituicao->id}");

                $user = User::firstOrCreate(
                    ['email' => $pending->user_email],
                    [
                        'nome' => $pending->user_nome,
                        'password' => Hash::make('12345678'),
                        'instituicao_id' => $instituicao->id,
                    ],
                );

                $userCreated = $user->wasRecentlyCreated;

                if ($user->instituicao_id !== $instituicao->id) {
                    $user->update(['instituicao_id' => $instituicao->id]);
                }

                Log::info("Utilizador: {$user->id}");

                if (! $user->hasRole('Director')) {
                    $user->assignRole('Director');
                }

                if ($userCreated) {
                    $user->notify(new TenantActivadoNotification(
                        nomeInstituicao: $instituicao->nome,
                        nomeUser: $user->nome,
                        email: $user->email,
                        subdomain: $this->tenant->id,
                        url: 'http://'.$this->tenant->id.'.'.env('APP_DOMAIN', 'localhost'),
                        sigla: $pending->sigla,
                    ));
                }

                $this->tenant->update([
                    'instituicao_id' => $instituicao->id,
                    'admin_user_id' => $user->id,
                ]);

                Log::info('Tenant atualizado com instituição e utilizador');
            });

            $pending->delete();
            Log::info('Dados pendentes deletados');
        });
    }
}
