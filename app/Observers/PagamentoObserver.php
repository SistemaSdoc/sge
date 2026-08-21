<?php

namespace App\Observers;

use App\Models\Tenant\Pagamento;

class PagamentoObserver
{
    public function created(Pagamento $pagamento)
    {
        // $pagamento->gerarRecibo();
    }
}
