<?php

namespace App\Actions\Central\Tenant;

use App\Helpers\BrowsershotHelper;
use App\Models\Central\Tenant;
use App\Services\Central\TenantMetricsService;
use App\Services\Central\TenantService;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportTenantUsersMetrics
{
    public function __invoke(
        Tenant $tenant,
        TenantService $tenantService,
        TenantMetricsService $metricsService,
    ): StreamedResponse {
        $tenant->loadMissing('domains');

        $metrics = $tenant->run(fn (): array => $metricsService->getUserMetrics());

        $instituicao = $tenantService->getInstituicao($tenant);
        $responsavel = $tenantService->getTenantAdminUser($tenant);
        $logoPath = public_path('images/tunganetu-02.jpg.jpeg');
        $logoData = file_get_contents($logoPath);

        $html = view('templates.tenant-relatorio', [
            'instituicao' => $instituicao?->nome ?? $tenant->instituicao_nome ?? $tenant->id,
            'responsavel' => $responsavel?->nome ?? 'Não definido',
            'dominio' => $tenant->domains->first()?->domain ?? 'Não definido',
            'dataEmissao' => now()->format('d/m/Y'),
            'inicioPeriodo' => now()->subDays(6)->format('d/m/Y'),
            'fimPeriodo' => now()->format('d/m/Y'),
            'users' => $metrics['users'],
            'logins' => $metrics['logins_semana'],
            'logoDataUri' => 'data:image/jpeg;base64,'.base64_encode($logoData),
        ])->render();

        $pdf = Browsershot::html($html)
            ->setChromePath(BrowsershotHelper::getChromePath())
            ->setNodeBinary(BrowsershotHelper::getNodeBinary())
            ->setNpmBinary(BrowsershotHelper::getNpmBinary())
            ->addChromiumArguments([
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--headless=new',
                '--no-zygote',
                '--single-process',
            ])
            ->timeout(60000)
            ->format('A4')
            ->portrait()
            ->noSandbox()
            ->margins(0, 0, 0, 0)
            ->pdf();

        $nomeInstituicao = Str::slug($instituicao?->nome ?? $tenant->instituicao_nome ?? $tenant->id);

        return response()->streamDownload(
            static function () use ($pdf): void {
                echo $pdf;
            },
            "metricas-usuarios-{$nomeInstituicao}.pdf",
            ['Content-Type' => 'application/pdf'],
        );
    }
}
