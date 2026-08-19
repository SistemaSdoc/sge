<?php

namespace App\Observers;

use App\Models\Pagamento;
use Illuminate\Support\Facades\Storage;

class PagamentoObserver
{
    public function created(Pagamento $pagamento)
    {
         // $pagamento->gerarRecibo();
    }
}