<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Certificado — {{ $candidato->nome }}</title>

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
            /* SEM display:flex — causa página em branco no Browsershot */
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

        /* ── Cabeçalho ── */
        .logo-area {
            text-align: center;
            margin-bottom: 4px;
        }

        .logo-placeholder {
            width: 60px;
            height: 60px;
            margin: 0 auto 4px;
        }

        .logo-placeholder img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        h1 {
            text-align: center;
            font-size: 13px;
            margin: 2px 0;
        }

        h2 {
            text-align: center;
            font-size: 12px;
            margin: 2px 0;
        }

        .subtitle {
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            margin: 3px 0;
            letter-spacing: 1px;
        }

        .cert-title {
            text-align: center;
            font-size: 18px;
            font-style: italic;
            margin: 4px 0 6px;
        }

        /* ── Corpo do texto ── */
        .corpo {
            font-size: 12px;
            line-height: 1.6;
            color: #111;
            text-align: justify;
            margin: 6px 0;
        }

        .nome-aluno {
            color: #c00;
            font-weight: bold;
        }

        /* ── Tabela de notas ── */
        .tabela-box {
            border: 2px solid #000;
            padding: 4px;
            margin-top: 6px;
        }

        table {
            width: 100%;
            height: 100%;
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

        td {
            border: 1px solid #d1d5db;
            padding: 5px 10px;
            color: #000;
        }

        .td-number {
            text-align: center;
            font-weight: bold;
        }

        .componente-header td {
            font-weight: bold;
            font-size: 10px;
            padding: 3px 5px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ── Rodapé ── */
        .footer-text {
            font-size: 11px;
            line-height: 1.5;
            margin-top: 6px;
            text-align: justify;
        }

        .date-line {
            text-align: center;
            font-size: 11px;
            margin: 6px 0 4px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
        }

        /* ── Assinaturas + QR ──
           Usando display:table em vez de flex para máxima compatibilidade ── */
        .footer-bottom {
            margin-top: 50px;
            width: 100%;
            display: table;
        }

        .assinaturas {
            display: table-cell;
            width: 60px;
            vertical-align: bottom;
        }

        .assinatura-wrap {
            width: 100%;
            display: table;
        }

        .assinatura,
        .assinatura-center {
            display: table-cell;
            width: 50px;
            font-size: 11px;
            text-align: center;
            vertical-align: bottom;
            padding: 0 10px;
        }

        .linha {
            position: relative;
            border-top: 1px solid #111;
            margin-top: 40px;
            height: 0;
        }

        .linha-com-phd .phd {
            position: absolute;
            right: 0;
            top: -8px;
            /* ajusta fino aqui */
            background: #fff;
            padding-left: 5px;
            font-size: 11px;
            font-style: italic;
        }

        .qr-cell {
            display: table-cell;
            width: 25%;
            text-align: center;
            vertical-align: bottom;
        }

        .qr-cell img {
            width: 60px;
            height: 60px;
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
                        {{-- public_path() garante que o Browsershot encontra a imagem no servidor --}}
                        <img src="{{ public_path('Emblem_of_Angola.svg.png') }}" alt="Emblema de Angola">
                    </div>
                </div>

                <div>
                    <h1>REPÚBLICA DE ANGOLA</h1>
                    <h2>MINISTÉRIO DA EDUCAÇÃO</h2>
                    <h2 class="subtitle">ENSINO SECUNDÁRIO TÉCNICO-PROFISSIONAL</h2>
                    <h2 class="cert-title">Certificado</h2>
                </div>

                {{-- Corpo --}}
                <div class="corpo">
                    &nbsp;&nbsp;&nbsp;&nbsp;<strong>Novais José</strong>, Director do Instituto Médio Comercial
                    de Luanda nº1141, criado sob o Decreto Executivo nº45/01, de 10 de Agosto, certifica que
                    <span class="nome-aluno">{{ $candidato->nome }}</span>

                    @if($candidato->data_nascimento)
                        nascid{{ $candidato->genero === 'F' ? 'a' : 'o' }} aos
                        {{ \Carbon\Carbon::parse($candidato->data_nascimento)->translatedFormat('d \d\e F \d\e Y') }},
                    @endif

                    @if($candidato->naturalidade)
                        natural de {{ $candidato->naturalidade }},
                    @endif

                    portador{{ $candidato->genero === 'F' ? 'a' : '' }} do Bilhete de Identidade
                    nº&nbsp;<strong>{{ $candidato->bi }}</strong>

                    @if($candidato->bi_local && $candidato->bi_data)
                        , passado pelo arquivo de Identificação de {{ $candidato->bi_local }}
                        aos {{ \Carbon\Carbon::parse($candidato->bi_data)->translatedFormat('d \d\e F \d\e Y') }},
                    @endif

                    concluiu no ano lectivo de <strong>2025/2026</strong>
                    o curso de <strong>{{ $curso->nome }}</strong>,
                    conforme o disposto na alínea f) do artigo 109º da LBSEE 17/16, de 7 de Outubro,
                    com a Média Final de <strong>
                        {{ $classificacao_final !== null ? number_format($classificacao_final, 0) : '—' }}
                        valores</strong>
                    obtido nas seguintes classificações por disciplinas,
                    conforme consta do processo Individual nº&nbsp;{{ $aluno->matricula ?? '—' }}.
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
                                <td>Média por Plano Curricular (PC)</td>
                                <td class="td-number">{{ $media_pc !== null ? number_format($media_pc, 0) : '—' }}</td>
                                <td>{{ $media_pc_extenso }}</td>
                            </tr>
                            <tr>
                                <td>Prova de Aptidão Profissional (PAP)</td>
                                <td class="td-number">{{ $nota_pap !== null ? number_format($nota_pap, 0) : '—' }}</td>
                                <td>{{ $nota_pap_extenso }}</td>
                            </tr>
                            <tr>
                                <td>Estágio Curricular Supervisionado (ECS)</td>
                                <td class="td-number">{{ $nota_ecs !== null ? number_format($nota_ecs, 0) : '—' }}</td>
                                <td>{{ $nota_ecs_extenso }}</td>
                            </tr>
                            <tr>
                                <td><strong>Classificação Final do Curso (4×PC + PAP + ECS) / 6</strong></td>
                                <td class="td-number">
                                    <strong>{{ $classificacao_final !== null ? number_format($classificacao_final, 0) : '—' }}</strong>
                                </td>
                                <td>{{ $classificacao_final_extenso }}</td>
                            </tr>

                        </tbody>
                    </table>
                </div>

                {{-- Rodapé legal --}}
                <div class="footer-text">
                    Para efeitos legais lhe é passado o presente CERTIFICADO, que consta no livro de registo
                    nº 03, folha nº 123, assinado por mim e autenticado com o carimbo a óleo e selo branco
                    em uso neste estabelecimento de ensino.
                </div>

                <div class="date-line">
                    <div>
                        {{ $instituicao->cidade ?? 'Luanda' }}, aos
                        {{ \Carbon\Carbon::now()->locale('pt')->translatedFormat('d \d\e F \d\e Y') }}
                    </div>

                    <div style="margin-top: 20px; ">
                        <div class="qr-cell">
                            <img src="data:image/png;base64,{{ $qrcode }}" alt="QR Code">
                        </div>
                    </div>
                </div>

                {{-- Assinaturas + QR Code --}}
                <div class="footer-bottom">
                    <div class="assinaturas">
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
                </div>

            </div>{{-- fim cert-inner --}}
        </div>{{-- fim cert-outer --}}
    </div>{{-- fim cert-wrap --}}

</body>

</html>