<?php

namespace App\Jobs;

use App\Models\Central\PendingTenantData;
use App\Models\Central\Tenant;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateTenantInstitution implements ShouldQueue
{
    use Queueable;

    public Tenant $tenant;

    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    public function handle(): void
    {
        try {
            $pending = PendingTenantData::where('tenant_id', $this->tenant->id)->first();

            if (!$pending) {
                return; // Sai silenciosamente
            }

            // Etapa: A criar instituição
            $this->guardarProgresso('criando_instituicao', 'A criar instituição...', 85);

            // Cria a instituição e utilizador
            DB::transaction(function () use ($pending) {
                $this->tenant->run(function () use ($pending) {
                    $instituicao = Instituicao::create([
                        'nome' => $pending->nome,
                        'sigla' => $pending->sigla,
                        'tipo' => $pending->tipo,
                        'status' => $pending->status,
                        'tenant_id' => $this->tenant->id,
                    ]);

                    $user = User::create([
                        'nome' => $pending->user_nome,
                        'email' => $pending->user_email,
                        'password' => Hash::make('12345678'),
                        'instituicao_id' => $instituicao->id,
                    ]);

                    $user->assignRole('Director');

                    $this->tenant->update([
                        'instituicao_id' => $instituicao->id,
                        'admin_user_id' => $user->id,
                    ]);
                });

                $pending->delete();
            });

            // Concluído!
            $this->guardarProgresso('concluido', 'Tenant criado com sucesso!', 100, 'concluido');
        } catch (\Exception $e) {
            $this->guardarProgresso('erro', "Erro: {$e->getMessage()}", 0, 'erro');
            throw $e;
        }
    }

    private function guardarProgresso(string $etapa, string $mensagem, int $percentagem, string $status = 'em_progresso'): void
    {
        Cache::put(
            "progresso_tenant_{$this->tenant->id}",
            [
                'etapa' => $etapa,
                'mensagem' => $mensagem,
                'percentagem' => $percentagem,
                'status' => $status,
            ],
            now()->addHour()
        );
    }
}
