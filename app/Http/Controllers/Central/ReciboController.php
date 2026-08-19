<?php

namespace App\Http\Controllers\Central;

use App\Models\Central\Pagamento;
use Illuminate\Support\Facades\Storage;

class ReciboController extends Controller
{
    public function exibir(Pagamento $pagamento)
    {
        $this->authorize('view', $pagamento);

        if (!$pagamento->recibo_path || !Storage::disk('local')->exists($pagamento->recibo_path)) {
            $pagamento->gerarRecibo(forcar: true);
        }

        return response()->file(Storage::disk('local')->path($pagamento->recibo_path));
    }

    public function exportar(Pagamento $pagamento)
{
    $this->authorize('view', $pagamento);

    if (!$pagamento->recibo_path || !Storage::disk('local')->exists($pagamento->recibo_path)) {
        $pagamento->gerarRecibo(forcar: true);
    }

    return response()->download(
        Storage::disk('local')->path($pagamento->recibo_path),
        "recibo-{$pagamento->numero_recibo}.pdf"
    );
}
}