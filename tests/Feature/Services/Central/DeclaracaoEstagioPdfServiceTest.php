<?php

use App\Models\Central\DeclaracaoEstagio;
use App\Services\Central\DeclaracaoEstagioPdfService;
use Illuminate\Support\Facades\Crypt;

it('generates a valid PDF for a declaration without saving it', function (): void {
    $payload = json_encode([
        'aluno_nome' => 'Estudante de Teste',
        'instituicao_nome' => 'Colégio de Teste',
        'nota' => '16.5',
        'avaliacao' => 'Muito bom',
        'empresa_nome' => 'Tunganetu',
        'emitido_em' => now()->format('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
    $token = rtrim(strtr(base64_encode(Crypt::encryptString($payload)), '+/', '-_'), '=');

    $declaracao = new DeclaracaoEstagio([
        'aluno_nome' => 'Estudante de Teste',
        'instituicao_nome' => 'Colégio de Teste',
        'instituicao_tipo' => 'colegio',
        'nota' => 16.5,
        'avaliacao' => 'Muito bom',
        'empresa_nome' => 'Tunganetu',
        'public_token' => $token,
        'emitido_em' => now(),
    ]);

    $conteudo = app(DeclaracaoEstagioPdfService::class)->gerar($declaracao);

    expect($conteudo)
        ->toStartWith('%PDF-')
        ->and($declaracao->exists)->toBeFalse();
});

it('renders the fixed three-month duration and 120-hour workload', function (): void {
    $html = view('pdf.declaracao-estagio', [
        'declaracao' => new DeclaracaoEstagio([
            'aluno_nome' => 'Estudante de Teste',
            'instituicao_nome' => 'Colégio de Teste',
            'emitido_em' => now(),
        ]),
        'tipoInstituicao' => 'Colégio',
        'logo' => null,
        'qrCode' => '',
    ])->render();

    expect($html)
        ->toContain('3 (três) meses')
        ->toContain('uma carga horária de')
        ->toContain('<span class="dados">120</span> horas.');
});
