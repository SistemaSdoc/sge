<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <title>Declaração de Estágio</title>
    <style>
        @page { margin: 24mm 20mm 26mm; }
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 10.5pt; line-height: 1.5; }

        /* ===== MOLDURA DECORATIVA (repete em todas as páginas) ===== */
        /* Moldura exterior: azul, espessa (a 6 mm da borda da folha) */
        .moldura-externa {
            border: 3px solid #163b66;
            bottom: -20mm;
            left: -14mm;
            position: fixed;
            right: -14mm;
            top: -18mm;
        }
        /* Moldura interior: fio fino dourado (a 9 mm da borda da folha) */
        .moldura-interna {
            border: 1px solid #b08d3c;
            bottom: -17mm;
            left: -11mm;
            position: fixed;
            right: -11mm;
            top: -15mm;
        }
        /* Quadrados ornamentais nos cantos */
        .canto {
            background: #163b66;
            border: 1px solid #b08d3c;
            height: 4mm;
            position: fixed;
            width: 4mm;
        }
        .canto-te { left: -12.5mm; top: -16.5mm; }
        .canto-td { right: -12.5mm; top: -16.5mm; }
        .canto-be { bottom: -18.5mm; left: -12.5mm; }
        .canto-bd { bottom: -18.5mm; right: -12.5mm; }

        /* Marca de água discreta (logótipo ou nome da empresa) */
        .marca-agua {
            color: #d3dbe6;
            font-size: 62pt;
            font-weight: bold;
            left: 0;
            letter-spacing: 4px;
            position: fixed;
            right: 0;
            text-align: center;
            top: 62mm;
            z-index: -1;
        }
        .marca-agua img { height: 55mm; opacity: 0.14; }

        .cabecalho { border-bottom: 2px solid #163b66; margin-bottom: 20px; padding-bottom: 10px; text-align: center; }
        .logo { height: 48px; margin-bottom: 4px; }
        .empresa { color: #163b66; font-size: 17pt; font-weight: bold; letter-spacing: 1px; line-height: 1.3; }
        .slogan { font-size: 10pt; }

        h1 {
            border-bottom: 1px solid #b08d3c;
            color: #163b66;
            font-size: 14pt;
            letter-spacing: 2px;
            margin: 22px auto;
            padding-bottom: 8px;
            text-align: center;
            width: 55%;
        }
        .texto { margin: 0 0 8px; text-align: justify; text-indent: 30px; }
        .dados { font-weight: bold; }
        .data { margin: 18px 0 0; text-align: right; }

        .assinatura-bloco { margin-top: 22px; width: 100%; }
        .assinatura-bloco td { text-align: center; vertical-align: bottom; }
        .assinatura { width: 60%; }
        .linha { border-top: 1px solid #374151; margin: 0 auto; padding-top: 6px; width: 250px; }
        .cargo { color: #4b5563; font-size: 9.5pt; }
        .qr img { height: 85px; width: 85px; }
        .qr p { color: #4b5563; font-size: 7.5pt; line-height: 1.3; margin: 2px 0 0; }

        .rodape {
            border-top: 1px solid #163b66;
            bottom: -14mm;
            color: #4b5563;
            font-size: 8pt;
            left: 0;
            line-height: 1.4;
            padding-top: 6px;
            position: fixed;
            right: 0;
            text-align: center;
        }
        .rodape strong { color: #163b66; }
    </style>
</head>
<body>
    <div class="moldura-externa"></div>
    <div class="moldura-interna"></div>
    <div class="canto canto-te"></div>
    <div class="canto canto-td"></div>
    <div class="canto canto-be"></div>
    <div class="canto canto-bd"></div>

    <div class="marca-agua">
        @if (!empty($logo))
            <img src="data:image/png;base64,{{ $logo }}" alt="">
        @else
            TUNGANETU
        @endif
    </div>

    <div class="rodape">
        <strong>Tunganetu</strong> &nbsp;|&nbsp; NIF: 5003407764<br>
        Luanda, Maculusso, Rua Luther King, Edifício dos Estudantes, 3º Andar, Apt. nº 12<br>
        Tel.: +244 923 678 529 / +244 927 800 505 &nbsp;|&nbsp; E-mail: tunganetu@gmail.com
    </div>

    <header class="cabecalho">
        @if (!empty($logo))
            <img class="logo" src="data:image/png;base64,{{ $logo }}" alt="Logótipo da Tunganetu"><br>
        @endif
        <div class="empresa">Tunganetu</div>
        <div class="slogan">Formação e desenvolvimento profissional</div>
    </header>

    <h1>DECLARAÇÃO DE ESTÁGIO</h1>

    <p class="texto">
        A <span class="dados">Tunganetu</span> declara, para os devidos efeitos, que
        <span class="dados">{{ $declaracao->aluno_nome }}</span>@if ($declaracao->bi), portador(a) do Bilhete de Identidade n.º <span class="dados">{{ $declaracao->bi }}</span>,@endif
        estudante
        @if ($declaracao->curso)
            do curso de <span class="dados">{{ $declaracao->curso }}</span>
            @if ($declaracao->ano)
                no ano lectivo de <span class="dados">{{ $declaracao->ano }}</span>
            @endif
            no
        @else
            do
        @endif
        {{ $tipoInstituicao }} <span class="dados">{{ $declaracao->instituicao_nome }}</span>,
        realizou nesta empresa um estágio de natureza <span class="dados">curricular</span>,
        com a duração de <span class="dados">3 (três) meses</span> e uma carga horária obrigatória de
        <span class="dados">120</span> horas.
    </p>

    @if ($declaracao->nota !== null || $declaracao->matricula || $declaracao->numero_processo)
        <p class="texto">
            @if ($declaracao->nota !== null)
                Nota final: <span class="dados">{{ number_format((float) $declaracao->nota, 1, ',', '.') }}</span> valores,
                com classificação <span class="dados">{{ $declaracao->avaliacao }}</span>.
            @endif
            @if ($declaracao->matricula)
                Matrícula: <span class="dados">{{ $declaracao->matricula }}</span>.
            @endif
            @if ($declaracao->numero_processo)
                Número de processo: <span class="dados">{{ $declaracao->numero_processo }}</span>.
            @endif
        </p>
    @endif

    <p class="texto">
        Durante o estágio, o(a) estagiário(a) integrou o ambiente de desenvolvimento da empresa,
        onde adquiriu formação prática em programação web, trabalhando com as ferramentas e
        tecnologias mais utilizadas actualmente no mercado, nomeadamente
        <span class="dados">Laravel, React, Next.js, Django, Bootstrap, Tailwind CSS, SQL, Git e GitHub</span>,
        tendo desenvolvido interfaces, lógica de back-end e modelação de bases de dados em contexto
        prático de projecto, e utilizado controlo de versões no trabalho em equipa.
    </p>

    <p class="data">Luanda, {{ $declaracao->emitido_em->format('d/m/Y') }}.</p>

    <table class="assinatura-bloco">
        <tr>
            <td class="assinatura">
                <div class="linha">{{ $declaracao->responsavel_nome ?? 'Tunganetu' }}</div>
                @if ($declaracao->responsavel_cargo)
                    <div class="cargo">{{ $declaracao->responsavel_cargo }}</div>
                @endif
            </td>
            <td class="qr">
                <img src="data:image/png;base64,{{ $qrCode }}" alt="Código QR para verificar a declaração">
                <p>Verifique a autenticidade<br>desta declaração pelo código QR</p>
            </td>
        </tr>
    </table>
</body>
</html>
