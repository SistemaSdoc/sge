<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Pagamento\GerarRecibo;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Pagamento;
use App\Services\Tenant\Recibos\ReciboPdfService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReciboController extends Controller
{
    public function __construct(
        private readonly GerarRecibo $gerarRecibo,
        private readonly ReciboPdfService $reciboPdfService,
    ) {}

    public function exibir(Pagamento $pagamento): BinaryFileResponse
    {
        $this->authorize('view', $pagamento);

        $caminho = $this->caminhoRecibo($pagamento);

        return response()->file($caminho, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="recibo-'.$pagamento->numero_recibo.'.pdf"',
        ]);
    }

    public function exportar(Pagamento $pagamento): BinaryFileResponse
    {
        $this->authorize('view', $pagamento);

        $caminho = $this->caminhoRecibo($pagamento);

        return response()->download(
            $caminho,
            "recibo-{$pagamento->numero_recibo}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }

    private function caminhoRecibo(Pagamento $pagamento): string
    {
        $caminhoRelativo = $this->gerarRecibo->handle($pagamento);

        abort_unless($this->reciboPdfService->existe($caminhoRelativo), 404, 'Recibo indisponível.');

        return $this->reciboPdfService->caminhoAbsoluto($caminhoRelativo);
    }
}
