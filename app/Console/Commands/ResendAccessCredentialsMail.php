<?php

namespace App\Console\Commands;

use App\Mail\AccessCredentialsMail;
use App\Models\Central\Tenant;
use App\Models\Tenant\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('mail:resend-access-credentials
    {--dry-run : Mostra os totais sem enviar emails}
    {--tenant= : Limita o reenvio a um tenant específico (pelo ID)}
    {--chunk=100 : Número de usuários processados por vez}'
)]
#[Description('Reenvia o email de credenciais de acesso a todos os usuários de todos os tenants')]
class ResendAccessCredentialsMail extends Command
{
    public function handle(): int
    {
        $tenants = $this->option('tenant')
            ? Tenant::whereKey($this->option('tenant'))->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->warn('Nenhum tenant encontrado.');

            return self::FAILURE;
        }

        $this->info("Tenants encontrados: {$tenants->count()}");

        if ($this->option('dry-run')) {
            $tenants->each(function (Tenant $tenant): void {
                $tenant->run(function () use ($tenant): void {
                    $total = User::count();
                    $this->line("  [{$tenant->id}] {$tenant->instituicao_nome} - {$total} usuários");
                });
            });

            $this->warn('[DRY RUN] Nenhum email foi enviado.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Confirmas o reenvio para todos os usuários de todos os tenants?')) {
            $this->info('Operação cancelada.');

            return self::SUCCESS;
        }

        $chunk = (int) $this->option('chunk');

        $totalEnviados = $tenants->sum(function (Tenant $tenant) use ($chunk): int {
            return $tenant->run(function () use ($tenant, $chunk): int {

                $this->info("A processar: [{$tenant->id}] {$tenant->instituicao_nome}");

                $enviados = 0;

                User::with('instituicao')
                    ->lazy($chunk)
                    ->each(function (User $user) use (&$enviados): void {

                        if (blank($user->email) || blank($user->instituicao)) {
                            return;
                        }

                        Mail::to($user->email)->queue(
                            new AccessCredentialsMail(
                                nome: $user->nome,
                                email: $user->email,
                                password: 12345678,
                                url: 'https://'.tenant()->domains->first()?->domain,
                                instituicao: $user->instituicao,
                                artigoInstituicao: 'da',
                            )
                        );

                        $enviados++;
                    });

                $this->line("  - {$enviados} emails despachados");

                return $enviados;
            });
        });

        $this->newLine();

        $this->info("Concluído. Total de emails despachados: {$totalEnviados}");

        return self::SUCCESS;
    }
}
