{{-- resources/views/pdf/recibo.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 13px; color: #222; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 16px; }
        .header p { margin: 2px 0; font-size: 11px; }
        .titulo { text-align: center; margin: 20px 0; }
        .titulo h2 { margin: 0; text-decoration: underline; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        td, th { border: 1px solid #ccc; padding: 6px 8px; text-align: left; font-size: 12px; }
        th { background: #f2f2f2; }
        .dados-aluno td, .dados-aluno th { width: 35%; }
        .total { text-align: right; font-weight: bold; margin-top: 10px; }

        /* Assinaturas: duas colunas lado a lado, espaçadas do conteúdo acima */
        .assinaturas {
            margin-top: 80px;
            width: 100%;
        }
        .assinatura-box {
            display: inline-block;
            width: 45%;
            text-align: center;
            vertical-align: top;
        }
        .assinatura-box.esquerda { margin-right: 4%; }
        .linha {
            border-top: 1px solid #333;
            margin-top: 60px;
            margin-left:auto;
            margin-right:auto;
            padding-top: 6px;
            font-size: 11px;
        }

        /* Footer fixo no fundo de cada página do PDF */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 10px;
            text-align: center;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    {{-- Footer precisa de estar logo após <body> para position:fixed funcionar no DomPDF --}}
    <div class="footer">
        Documento válido como comprovativo de pagamento mediante autenticação com carimbo e assinatura.
    </div>

    {{-- Cabeçalho = identificação do colégio emissor, não pagador --}}
    <div class="header">
        @if($instituicao->logo ?? false)
            <img src="{{ public_path('storage/' . $instituicao->logo) }}" style="max-height:70px;">
        @endif
        <h1>{{ $instituicao->nome }}</h1>
        <p>{{ $instituicao->morada ?? '' }}</p>
        <p>NIF: {{ $instituicao->nif ?? '-' }} | Tel: {{ $instituicao->telefone ?? '-' }}</p>
    </div>

    <div class="titulo">
        <h2>RECIBO DE PAGAMENTO</h2>
        <p>Nº {{ $numeroRecibo }} — {{ $pagamento->data_pagamento->format('d/m/Y') }}</p>
    </div>

    @php
    $turma = $pagamento->aluno->turmaActual->first();
    $cursoClasse = $turma?->cursoClasseTurno?->cursoClasse;
    @endphp

    @php
    $mesesPt = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
    ];
    @endphp

    {{-- Pagador real: o aluno --}}
    <table class="dados-aluno">
        <tr><th>Aluno</th><td>{{ $pagamento->aluno->user->nome ?? '-'  }}</td></tr>
        <tr><th>Curso</th><td>{{ $cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome ?? '-' }}</td></tr>
        <tr><th>Classe</th><td>{{ $cursoClasse?->classe?->nome ?? '-' }}</td></tr>
        <tr><th>Turma</th><td>{{ $turma?->nome ?? '-' }}</td></tr>
        <tr><th>Método de Pagamento</th><td>{{ $pagamento->metodo }}</td></tr>
        {{-- <tr><th>Referência</th><td>{{ $pagamento->referencia ?? '-' }}</td></tr> --}}
        <tr><th>Registado por</th><td>{{ $pagamento->registadoPor->nome ?? '-' }}</td></tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Mês/Ano</th>
                <th>Valor Esperado</th>
                <th>Valor Pago</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pagamento->itens as $item)
                <tr>
                    <td>{{ $item->itemPagavel->nome }}</td>
                     <td>{{ $mesesPt[$item->mes] ?? $item->mes }}/{{ $item->ano }}</td>
                    <td>{{ number_format($item->valor, 2, ',', '.') }} Kz</td>
                    <td>{{ number_format($item->valor, 2, ',', '.') }} Kz</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="total">Total Pago: {{ number_format($pagamento->valor_total, 2, ',', '.') }} Kz</p>

    @if($pagamento->observacoes)
        <p><strong>Observações:</strong> {{ $pagamento->observacoes }}</p>
    @endif

    <div class="assinaturas">

        <div class="assinatura-box">
            <div class="linha">Assinatura do Responsável</div>
        </div>
    </div>
</body>
</html>