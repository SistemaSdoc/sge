<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <title>Quadro de Honra - {{ $quadroHonra->anoLectivo?->nome ?? $quadroHonra->ano_lectivo_id }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1c2433; margin: 0; }
        .cabecalho { text-align: center; border: 1px solid #1c2433; padding: 10px; }
        .cabecalho h1 { margin: 0 0 4px; font-size: 15pt; text-transform: uppercase; }
        .cabecalho p { margin: 3px 0; color: #4a5568; }
        .instituicao { font-size: 10pt; font-weight: bold; }
        .alunos { width: 100%; border-collapse: collapse; border: 1px solid #1c2433; border-top: 0; }
        .alunos td { width: 33.333%; height: 47mm; padding: 8px; border: 1px solid #8a93a3; vertical-align: middle; }
        .cartao { width: 100%; border-collapse: collapse; }
        .cartao td { border: 0; padding: 2px; height: auto; vertical-align: top; }
        .foto { width: 25mm; height: 33mm; border: 1px dashed #1c2433; color: #7b8492; text-align: center; vertical-align: middle !important; font-size: 7pt; }
        .dados { padding-left: 7px !important; line-height: 1.55; }
        .dados strong { color: #1c2433; }
        .rodape { width: 100%; border: 1px solid #1c2433; border-top: 0; border-collapse: collapse; }
        .rodape td { padding: 13px 10px; }
        .assinatura { width: 65%; }
        .linha { display: inline-block; width: 55mm; height: 12px; border-bottom: 1px solid #1c2433; }
        .vazio { text-align: center; color: #667085; height: 20mm !important; }
    </style>
</head>
<body>
    @php
        $itens = $quadroHonra->itens;
        $linhas = (int) ceil($itens->count() / 3);
        $instituicao = $quadroHonra->geradoPor?->instituicao;
        $tipo = $quadroHonra->tipo->value === 'geral' ? 'Quadro Geral' : 'Quadro por Curso';
        $curso = $quadroHonra->itens->first()?->curso;
    @endphp

    <div class="cabecalho">
        @if($instituicao?->nome)
            <p class="instituicao">{{ $instituicao->nome }}</p>
        @endif
        <h1>Quadro de Honra</h1>
        <p>{{ $tipo }}@if($curso) — {{ $curso }}@endif</p>
        <p>Ano lectivo {{ $quadroHonra->anoLectivo?->nome ?? $quadroHonra->ano_lectivo_id }}</p>
    </div>

    <table class="alunos">
        @if($itens->isEmpty())
            <tr><td colspan="3" class="vazio">Não foram encontrados alunos elegíveis para este quadro.</td></tr>
        @else
            {{-- O espaço vazio da ficha foi pedido para a fotografia ser colada depois da impressão. --}}
            @for($linha = 0; $linha < $linhas; $linha++)
                <tr>
                    @for($coluna = 0; $coluna < 3; $coluna++)
                        @php($item = $itens->get($linha * 3 + $coluna))
                        <td>
                            @if($item)
                                <table class="cartao">
                                    <tr>
                                        <td class="foto">ESPAÇO<br>PARA FOTO</td>
                                        <td class="dados">
                                            <strong>{{ $item->posicao }}. {{ $item->nome_aluno }}</strong><br>
                                            Curso: {{ $item->curso }}<br>
                                            Classe: {{ $item->classe }}<br>
                                            Turma: {{ $item->turma }}<br>
                                            Média: <strong>{{ number_format((float) $item->media, 2, ',', '.') }}</strong>
                                        </td>
                                    </tr>
                                </table>
                            @endif
                        </td>
                    @endfor
                </tr>
            @endfor
        @endif
    </table>

    <table class="rodape">
        <tr>
            <td class="assinatura"><strong>Assinatura do Director:</strong> <span class="linha"></span></td>
            <td><strong>Data:</strong> ____ / ____ / ________</td>
        </tr>
    </table>
</body>
</html>
