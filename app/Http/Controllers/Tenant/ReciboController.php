<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Pagamento\GerarRecibo;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Pagamento;
use App\Services\Tenant\Recibos\ReciboPdfService;
use Illuminate\Http\Response;

class ReciboController extends Controller
{
    public function __construct(
        private readonly GerarRecibo $gerarRecibo,
        private readonly ReciboPdfService $reciboPdfService,
    ) {}

    public function exibir(Pagamento $pagamento): Response
    {
        $this->authorize('view', $pagamento);

        $caminho = $this->caminhoRecibo($pagamento);

        return response($this->reciboPdfService->conteudo($caminho), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="recibo-'.$pagamento->numero_recibo.'.pdf"',
        ]);
    }

    public function exportar(Pagamento $pagamento): Response
    {
        $this->authorize('view', $pagamento);

        $caminho = $this->caminhoRecibo($pagamento);

        return response($this->reciboPdfService->conteudo($caminho), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="recibo-'.$pagamento->numero_recibo.'.pdf"',
        ]);
    }

    private function caminhoRecibo(Pagamento $pagamento): string
    {
        $caminhoRelativo = $this->gerarRecibo->handle($pagamento);

        abort_unless($this->reciboPdfService->existe($caminhoRelativo), 404, 'Recibo indisponível.');

        return $caminhoRelativo;
    }
}
