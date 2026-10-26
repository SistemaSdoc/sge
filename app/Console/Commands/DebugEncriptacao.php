<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use App\Models\Tenant\PlanificacaoVersao;
use App\Services\Tenant\PlanificacaoStorageService;
use Illuminate\Console\Command;

class DebugEncriptacao extends Command
{
    protected $signature = 'debug:encriptacao {tenant=cs.app.pge.edu.ao}';
    protected $description = 'Verifica se os uploads de planificações estão encriptados';

    public function handle(PlanificacaoStorageService $service): void
    {
        $tenantId = $this->argument('tenant');
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $this->error("Tenant não encontrado: {$tenantId}");
            $this->line('Tenants disponíveis:');
            Tenant::all()->each(fn ($t) => $this->line("  - {$t->id}"));
            return;
        }

        tenancy()->initialize($tenant);

        try {
            $v = PlanificacaoVersao::latest()->first();

            if (! $v) {
                $this->warn("Sem uploads no tenant {$tenant->id}.");
                return;
            }

            $this->info('══════ VERIFICAÇÃO DE ENCRIPTAÇÃO ══════');
            $this->line("Tenant: {$tenant->id}");
            $this->line("BD:     {$tenant->tenancy_db_name}");
            $this->newLine();

            // Encontrar o ficheiro físico (tenta vários caminhos)
            $candidatos = [
                base_path("storage/tenant{$tenant->id}/app/{$v->caminho_ficheiro}"),
                base_path("storage/tenantcs.app.pge.edu.ao/app/{$v->caminho_ficheiro}"),
                base_path("storage/tenant_ctn_database/app/{$v->caminho_ficheiro}"),
                storage_path("app/{$v->caminho_ficheiro}"),
            ];

            $caminho = null;
            foreach ($candidatos as $c) {
                if (file_exists($c)) {
                    $caminho = $c;
                    break;
                }
            }

            if (! $caminho) {
                $this->error('Ficheiro físico não encontrado. Caminhos testados:');
                foreach ($candidatos as $c) {
                    $this->line("  - {$c}");
                }
                return;
            }

            $this->line("Ficheiro: {$caminho}");
            $this->newLine();

            $conteudoDisco = file_get_contents($caminho);

            // ── 1. Metadata ──
            $this->info('1. Metadata (BD):');
            $this->table(['Campo', 'Valor'], [
                ['encriptado', $v->encriptado ? 'sim' : 'NÃO'],
                ['comprimido', $v->comprimido ? 'sim' : 'não'],
                ['algoritmo', $v->algoritmo_encriptacao ?? '—'],
                ['original', $v->tamanho_original_bytes . ' bytes'],
                ['final', $v->tamanho_bytes . ' bytes'],
                ['redução', $v->reducao_percentual . '%'],
            ]);

            // ── 2. Magic bytes ──
            $this->newLine();
            $this->info('2. Magic bytes no disco:');
            $magic = bin2hex(substr($conteudoDisco, 0, 4));
            $this->line("   4 primeiros: {$magic}");
            $this->line('   É PDF? ' . ($magic === '25504446' ? 'SIM (NÃO encriptado!)' : 'NÃO (encriptado)'));

            // ── 3. Tamanho ──
            $this->newLine();
            $this->info('3. Tamanho disco vs BD:');
            $this->line('   Disco: ' . filesize($caminho) . ' bytes');
            $this->line("   BD:    {$v->tamanho_bytes} bytes");
            $this->line('   Bate?  ' . (filesize($caminho) === $v->tamanho_bytes ? 'sim' : 'NÃO'));

            // ── 4. IV ──
            $this->newLine();
            $this->info('4. IV (16 bytes iniciais):');
            $this->line('   ' . bin2hex(substr($conteudoDisco, 0, 16)));

            // ── 5. Desencriptação ──
            $this->newLine();
            $this->info('5. Desencriptação:');
            $plano = $service->conteudo($v);
            $this->line('   Resultado: ' . ($plano ? 'OK' : 'NULL (falhou)'));
            if ($plano) {
                $this->line('   Tamanho:   ' . strlen($plano) . ' bytes');
                $this->line('   Magic:     ' . bin2hex(substr($plano, 0, 4)));
                $this->line('   É PDF?     ' . (substr($plano, 0, 4) === '%PDF' ? 'SIM' : 'NÃO'));
            }

            // ── 6. Autenticação GCM ──
            $this->newLine();
            $this->info('6. Autenticação AES-GCM (teste de corrupção):');
            $meio = intdiv(strlen($conteudoDisco), 2);
            $corrompido = $conteudoDisco;
            $corrompido[$meio] = chr(ord($corrompido[$meio]) ^ 0xFF);
            file_put_contents($caminho, $corrompido);
            $resultadoCorrupto = $service->conteudo($v);
            file_put_contents($caminho, $conteudoDisco);

            $this->line('   Corromper 1 byte: ' . ($resultadoCorrupto === null ? 'rejeitou (correto)' : 'aceitou lixo (ERRADO)'));
            $this->line('   (AES-GCM detectou a corrupção via Tag)');

            $this->newLine();
            $this->info('══════ FIM ══════');
        } finally {
            tenancy()->end();
        }
    }
}