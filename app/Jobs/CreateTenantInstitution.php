<?php

namespace App\Jobs;

use App\Models\Central\PendingTenantData;
use App\Models\Central\Tenant;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\User;
use App\Services\Central\TenantCreateProgressService;
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
    public function handle(TenantCreateProgressService $progressService): void
    {
        try {
            Log::info("CreateTenantInstitution iniciado para tenant: {$this->tenant->id}");

            $pending = PendingTenantData::where('tenant_id', $this->tenant->id)->first();

            if (! $pending) {
                Log::info("Sem dados pendentes para tenant: {$this->tenant->id}");

                return;
            }

            $progressService->save($this->tenant, [
                'etapa' => 'criando_instituicao',
                'mensagem' => 'A criar instituição...',
                'percentagem' => 85,
                'status' => 'em_progresso',
            ]);

            $this->createInstitutionAndUser($pending);

            $progressService->save($this->tenant, [
                'etapa' => 'concluido',
                'mensagem' => 'Tenant criado com sucesso!',
                'percentagem' => 100,
                'status' => 'concluido',
            ]);

            Log::info("CreateTenantInstitution concluído para tenant: {$this->tenant->id}");
        } catch (\Exception $e) {
            Log::error("Erro em CreateTenantInstitution: {$e->getMessage()}");
            $progressService->save($this->tenant, [
                'etapa' => 'erro',
                'mensagem' => "Erro: {$e->getMessage()}",
                'percentagem' => 0,
                'status' => 'erro',
            ]);
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

                $instituicao = Instituicao::create([
                    'nome' => $pending->nome,
                    'sigla' => $pending->sigla,
                    'tipo' => $pending->tipo,
                    'status' => $pending->status,
                    'tenant_id' => $this->tenant->id,
                ]);

                Log::info("Instituição criada: {$instituicao->id}");

                $user = User::create([
                    'nome' => $pending->user_nome,
                    'email' => $pending->user_email,
                    'password' => Hash::make('12345678'),
                    'instituicao_id' => $instituicao->id,
                ]);

                Log::info("Utilizador criado: {$user->id}");

                $user->assignRole('Director');

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
