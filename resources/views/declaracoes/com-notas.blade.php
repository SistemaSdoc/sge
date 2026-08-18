<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Declaração-Com-Notas — {{ $candidato->nome }}</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            width: 210mm;
            height: 297mm;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .cert-wrap {
            width: 210mm;
            height: 297mm;
            padding: 8mm;
            font-family: 'Georgia', serif;
            overflow: hidden;
        }

        /* ── Borda exterior colorida (verde/laranja) ── */
        .cert-outer {
            position: relative;
            width: 100%;
            height: 100%;
            background-color: #fff;
            background-image:
                repeating-linear-gradient(to right, #16a34a 0 20px, #f97316 20px 40px),
                repeating-linear-gradient(to right, #16a34a 0 20px, #f97316 20px 40px),
                repeating-linear-gradient(to bottom, #16a34a 0 20px, #f97316 20px 40px),
                repeating-linear-gradient(to bottom, #16a34a 0 20px, #f97316 20px 40px);
            background-size: 100% 20px, 100% 20px, 20px 100%, 20px 100%;
            background-position: top, bottom, left, right;
            background-repeat: no-repeat;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            overflow: hidden;
        }

        /* Borda azul interna */
        .cert-outer::after {
            content: "";
            position: absolute;
            inset: 8px;
            border: 4px solid #1e3a8a;
            border-radius: 6px;
            pointer-events: none;
            z-index: 2;
        }

        /* ── Círculos nos cantos ── */
        .corner-circle {
            position: absolute;
            width: 16px;
            height: 16px;
            border: 4px solid #1e3a8a;
            border-radius: 50%;
            background: transparent;
            z-index: 3;
        }

        .cc-tl {
            top: 1px;
            left: 1px;
        }

        .cc-tr {
            top: 1px;
            right: 1px;
        }

        .cc-bl {
            bottom: 1px;
            left: 1px;
        }

        .cc-br {
            bottom: 1px;
            right: 1px;
        }

        /* ── Marcas d'água ── */
        .watermark {
            position: absolute;
            font-size: 22px;
            font-weight: bold;
            color: rgba(4, 139, 4, 0.85);
            z-index: 1;
            pointer-events: none;
            white-space: nowrap;
        }

        .wm1 {
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
        }

        .wm2 {
            top: 15%;
            left: 15%;
            transform: rotate(-30deg);
        }

        .wm3 {
            top: 15%;
            right: 15%;
            transform: rotate(-30deg);
        }

        .wm4 {
            bottom: 15%;
            left: 15%;
            transform: rotate(-30deg);
        }

        .wm5 {
            bottom: 7%;
            right: 15%;
            transform: rotate(-30deg);
        }

        /* ── Área interior ── */
        .cert-inner {
            position: relative;
            z-index: 2;
            margin: 26px;
            padding: 8px 14px;
            height: calc(100% - 52px);
            background: rgba(255, 255, 255, 0.75);
            overflow: hidden;
        }

        .logo-area {
            text-align: center;
            margin-bottom: 2px;
        }

        .logo-placeholder {
            width: 55px;
            height: 55px;
            margin: 0 auto 2px;
        }

        h1 {
            text-align: center;
            font-size: 13px;
            margin: 1px 0;
        }

        h2 {
            text-align: center;
            font-size: 12px;
            margin: 1px 0;
        }

        .subtitle {
            text-align: center;
            font-size: 11px;
            font-weight: 700;
            margin: 2px 0;
            letter-spacing: 1px;
        }

        /* Espaço entre o Ensino Secundário e a Declaração */
        .cert-title {
            text-align: center;
            font-size: 17px;
            font-weight: bold;
            margin: 14px 0 3px;
            letter-spacing: 1.5px;
        }

        .cert-subtitle {
            text-align: center;
            font-size: 10.5px;
            font-style: italic;
            margin: 0 0 10px;
        }

        /* ── Corpo do texto ── */
        /* ── Corpo da declaração ── */
        .corpo {
            font-size: 12px;
            line-height: 1.5;
            color: #111;
            text-align: justify;
            margin: 10px 0 6px;
        }


        .logo-placeholder img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }


        .nome-aluno {
            color: #c00;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* ── Tabela de notas ── */
        .tabela-box {
            border: 2px solid #000;
            padding: 3px;
            margin-top: 6px;
        }

        td {
            border: 1px solid #d1d5db;
            padding: 3px 8px;
            color: #000;
        }

        .componente-header td {
            font-weight: bold;
            font-size: 10px;
            padding: 2px 5px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            border: 2px solid #000;
        }

        th {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            color: #000;
            padding: 3px 5px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #ccc;
        }

        .td-number {
            text-align: center;
            font-weight: bold;
        }

        /* ── Rodapé ── */
        .footer-text {
            font-size: 11px;
            line-height: 1.6;
            margin-top: 8px;
            text-align: justify;
        }

        .date-line {
            text-align: center;
            font-size: 11px;
            margin: 8px 0 4px;
        }

        /* ── Assinaturas ── */
        .footer-bottom {
            margin-top: 16px;
            width: 100%;
            display: table;
        }

        .assinatura-wrap {
            width: 100%;
            display: table;
            table-layout: fixed;
        }

        .assinatura,
        .assinatura-center {
            display: table-cell;
            width: 50%;
            font-size: 11px;
            text-align: center;
            vertical-align: bottom;
            padding: 0 10px;
        }

        .linha {
            position: relative;
            border-top: 1px solid #111;
            margin-top: 20px;
            height: 0;
            width: 65%;
            margin-left: auto;
            margin-right: auto;
        }

        .linha-com-phd .phd {
            position: absolute;
            right: 0;
            top: -8px;
            background: #fff;
            padding-left: 5px;
            font-size: 11px;
            font-style: italic;
        }

        /* ── Print ── */
        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }

            html,
            body {
                width: 210mm;
                height: 297mm;
                overflow: hidden;
            }

            .cert-outer {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>

    <div class="cert-wrap">
        <div class="cert-outer">

            {{-- Círculos nos cantos --}}
            <div class="corner-circle cc-tl"></div>
            <div class="corner-circle cc-tr"></div>
            <div class="corner-circle cc-bl"></div>
            <div class="corner-circle cc-br"></div>

            {{-- Marcas d'água --}}
            <div class="watermark wm1">IMCL</div>
            <div class="watermark wm2">IMCL</div>
            <div class="watermark wm3">IMCL</div>
            <div class="watermark wm4">IMCL</div>
            <div class="watermark wm5">IMCL</div>

            <div class="cert-inner">

                {{-- Cabeçalho --}}
                <div class="logo-area">
                    <div class="logo-placeholder">
                        <img src="{{ public_path('Emblem_of_Angola.svg.png') }}" alt="Emblema de Angola">
                    </div>
                </div>

                <div>
                    <h1>REPÚBLICA DE ANGOLA</h1>
                    <h2>MINISTÉRIO DA EDUCAÇÃO</h2>
                    <h2 class="subtitle">ENSINO SECUNDÁRIO TÉCNICO-PROFISSIONAL</h2>
                    <div class="cert-title">DECLARAÇÃO Nº{{ $numero_declaracao }}/SP/{{ date('Y') }}</div>
                    <div class="cert-subtitle">Para efeitos de frequência e aproveitamento escolar</div>
                </div>

                {{-- Corpo da declaração --}}
                {{-- Corpo da declaração --}}
                <div class="corpo">

                    &nbsp;&nbsp;&nbsp;&nbsp;Para efeitos de
                    <strong>frequência e aproveitamento escolar</strong>, declara-se que
                    <span class="nome-aluno">{{ $candidato->nome }}</span>,

                    @if($candidato->pai || $candidato->mae)
                    Filho{{ $candidato->genero === 'F' ? 'a' : '' }}
                    de <strong>{{ $candidato->pai ?? '—' }}</strong>
                    e de <strong>{{ $candidato->mae ?? '—' }}</strong>,
                    @endif

                    @if($candidato->data_nascimento)
                    nascid{{ $candidato->genero === 'F' ? 'a' : 'o' }}
                    aos
                    {{ \Carbon\Carbon::parse($candidato->data_nascimento)->locale('pt')->translatedFormat('d \d\e F \d\e Y') }},
                    @endif

                    @if($candidato->naturalidade)
                    natural de <strong>{{ $candidato->naturalidade }}</strong>,
                    @endif

                    @if($candidato->bi)
                    portador{{ $candidato->genero === 'F' ? 'a' : 'o' }}
                    do Bilhete de Identidade nº
                    <strong>{{ $candidato->bi }}</strong>

                    @if($candidato->bi_local && $candidato->bi_data)
                    , passado pelo Arquivo de Identificação de
                    <strong>{{ $candidato->bi_local }}</strong>
                    aos
                    {{ \Carbon\Carbon::parse($candidato->bi_data)->translatedFormat('d \d\e F \d\e Y') }}
                    @endif
                    ,
                    @endif

                    frequentou neste
                    <strong>{{ $instituicao->nome ?? 'Instituto' }}</strong>
                    no ano lectivo <strong>{{ $ano_lectivo }}</strong>,
                    a <strong>{{ $classe->nome ?? '—' }}</strong> classe
                    do curso de <strong>{{ $curso->nome }}</strong>,
                    da Área de Formação de
                    <strong>{{ $area_formacao ?? $curso->nome }}</strong>,
                    da turma <strong>{{ $turma->nome }}</strong>,
                    sob o processo nº <strong>{{ $aluno->matricula ?? '—' }}</strong>,
                    do turno da <strong>{{ $turno ?? 'Diúrno' }}</strong>,
                    tendo como resultado final
                    <strong>{{ $resultado_final }}</strong>,
                    com a Média de
                    <strong>
                        {{ $classificacao_final !== null
                        ? number_format($classificacao_final, 0)
                        : '—' }}
                        valores
                    </strong>,
                    conforme as seguintes classificações por disciplinas.

                </div>

                {{-- Tabela de notas --}}
                <div class="tabela-box">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:58%">Disciplina</th>
                                <th style="width:16%">Média Final</th>
                                <th style="width:26%">Média por Extenso</th>
                            </tr>
                        </thead>
                        <tbody>

                            @if(!empty($notas['sociocultural']))
                            <tr class="componente-header">
                                <td colspan="3">Componente Sociocultural</td>
                            </tr>
                            @foreach($notas['sociocultural'] as $nota)
                            <tr>
                                <td>{{ $nota['disciplina'] }}</td>
                                <td class="td-number">{{ number_format($nota['media_final'], 0) }}</td>
                                <td>{{ $nota['extenso'] }}</td>
                            </tr>
                            @endforeach
                            @endif

                            @if(!empty($notas['cientifica']))
                            <tr class="componente-header">
                                <td colspan="3">Componente Científica</td>
                            </tr>
                            @foreach($notas['cientifica'] as $nota)
                            <tr>
                                <td>{{ $nota['disciplina'] }}</td>
                                <td class="td-number">{{ number_format($nota['media_final'], 0) }}</td>
                                <td>{{ $nota['extenso'] }}</td>
                            </tr>
                            @endforeach
                            @endif

                            @if(!empty($notas['tecnica']))
                            <tr class="componente-header">
                                <td colspan="3">Componente Técnica, Tecnológica e Prática</td>
                            </tr>
                            @foreach($notas['tecnica'] as $nota)
                            <tr>
                                <td>{{ $nota['disciplina'] }}</td>
                                <td class="td-number">{{ number_format($nota['media_final'], 0) }}</td>
                                <td>{{ $nota['extenso'] }}</td>
                            </tr>
                            @endforeach
                            @endif

                            <tr>
                                <td><strong>Média por Plano Curricular (PC)</strong></td>
                                <td class="td-number">{{ $media_pc !== null ? number_format($media_pc, 0) : '—' }}</td>
                                <td>{{ $media_pc_extenso }}</td>
                            </tr>

                        </tbody>
                    </table>
                </div>

                {{-- Parágrafo de autenticação --}}
                <div class="footer-text">
                    Por ser verdade e me ter sido solicitado, mandei passar a presente declaração que vai por mim
                    assinada e autenticada com carimbo a óleo em uso neste
                    {{ $instituicao->nome ?? 'Instituto Médio Comercial de Luanda' }}.
                </div>

                <div class="date-line">
                    {{ $instituicao->cidade ?? 'Luanda' }}, aos
                    {{ \Carbon\Carbon::now()->locale('pt')->translatedFormat('d \d\e F \d\e Y') }}
                </div>

                {{-- Assinaturas --}}
                <div class="footer-bottom">
                    <div class="assinatura-wrap">
                        <div class="assinatura">
                            <strong>O Sub-Director Pedagógico</strong>
                            <div class="linha"></div>
                        </div>

                        <div class="assinatura-center">
                            <strong>O Director</strong>
                            <div class="linha linha-com-phd">
                                <span class="phd">Ph.D</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>{{-- fim cert-inner --}}
        </div>{{-- fim cert-outer --}}
    </div>{{-- fim cert-wrap --}}

</body>

</html>