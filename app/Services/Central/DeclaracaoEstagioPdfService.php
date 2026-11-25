<?php

namespace App\Services\Central;

use App\Models\Central\DeclaracaoEstagio;
use App\Services\Tenant\QrCodeService;
use Barryvdh\DomPDF\Facade\Pdf;

class DeclaracaoEstagioPdfService
{
    public function gerar(DeclaracaoEstagio $declaracao): string
    {
        $urlVerificacao = route('central.verificar-estagio', [
            'token' => $declaracao->public_token,
        ]);
        $caminhoLogo = public_path('images/tunganetu-02.png');

        return Pdf::loadView('pdf.declaracao-estagio', [
            'declaracao' => $declaracao,
            'urlVerificacao' => $urlVerificacao,
            'qrCode' => QrCodeService::generate($urlVerificacao),
            'tipoInstituicao' => $declaracao->instituicao_tipo === 'colegio' ? 'Colégio' : 'Instituto',
            'logo' => is_file($caminhoLogo) ? base64_encode(file_get_contents($caminhoLogo)) : null,
        ])->setPaper('a4')->output();
    }
}
