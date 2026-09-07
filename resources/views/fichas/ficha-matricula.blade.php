@php
    $escola = mb_strtoupper($escola ?? '');
@endphp
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <title>Ficha de Matrícula</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .pagina {
            width: 21cm;
            min-height: 29.7cm;
            padding: 2cm 1.5cm 1cm 1.5cm;
            border: 3px solid #000;
        }

        .via {
            padding-top: 0.5cm;
        }

        .via-2 {
            padding-top: 1.4cm;
        }

        .escola-titulo {
            text-align: center;
            font-weight: bold;
            font-size: 16pt;
            margin: 0 0 0.6cm 0;
        }

        /* ===== Cabeçalho (via 1: com caixa de foto) ===== */
        .header-tabela {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0.5cm;
        }

        .header-tabela td {
            vertical-align: middle;
            padding: 0;
        }

        .header-titulo {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
        }

        .foto-caixa {
            width: 3.5cm;
            height: 4.5cm;
            border: 1.5px solid #000;
            text-align: center;
            vertical-align: middle;
            font-size: 8pt;
            color: #999;
        }

        .header-simples {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 0.5cm;
        }

        /* ===== Texto corrido (parágrafo justificado) ===== */
        .texto-corrido {
            text-align: justify;
            line-height: 1.5;
            margin: 0 0 0.5cm 0;
        }

        /* ===== Assinaturas ===== */
        .assinaturas {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.2cm;
        }

        .assinaturas td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 0.5cm;
        }

        .assinaturas .rotulo {
            font-size: 11pt;
            margin-bottom: 0.4cm;
        }

        .assinaturas .linha {
            font-size: 12pt;
        }

        .local-data {
            text-align: center;
            font-size: 11pt;
            margin-top: 1cm;
        }

        /* ===== Linha divisória entre as vias ===== */
        .divisoria {
            margin: 0.8cm 0;
        }

        .divisoria .linha-igual {
            width: 100%;
            overflow: hidden;
            white-space: nowrap;
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
            font-size: 13pt;
            letter-spacing: 1px;
            line-height: 1;
        }
    </style>
</head>
<body>
    <div class="pagina">

        {{-- ===================== VIA 1 (com foto) ===================== --}}
        <div class="via via-1">
            <div class="escola-titulo">{{ $escola }}</div>

            <table class="header-tabela">
                <tr>
                    <td class="header-titulo">
                        FICHA&nbsp;&nbsp;&nbsp;DE&nbsp;&nbsp;&nbsp;MATRÍCULA&nbsp;&nbsp;&nbsp;{{ $numero_processo ?: '_____' }}
                    </td>
                    <td class="foto-caixa">FOTO</td>
                </tr>
            </table>

            <p class="texto-corrido">
                Processo nº {{ $numero_processo ?: '_____' }},
                idade {{ $idade ?: '___' }} anos,
                Sexo {{ $sexo ?: '_______' }},
                Nome {{ $nome ?: '_______________________' }},
                Nascido(a) aos {{ $data_nascimento ?: '__/__/____' }}
                em {{ $local_nascimento ?: '_______________' }},
                Portador do BI/Céd. Nº {{ $numero_bi ?: '_______________' }},
                Filho(a) de {{ $nome_pai ?: '_______________________' }}
                e de {{ $nome_mae ?: '_______________________' }},
                Inscreve-se na(o) {{ $classe ?: '_____' }}
                no turno da {{ $turno ?: '_______' }}
                Tel {{ $telefone ?: '_________' }},
                Curso {{ $curso ?: '_______________________' }}.
            </p>

            <table class="assinaturas">
                <tr>
                    <td>
                        <div class="rotulo">Nome do(a) Encarregado(a) de Educação</div>
                        <div class="linha">______________________________</div>
                    </td>
                    <td>
                        <div class="rotulo">O Director(a)</div>
                        <div class="linha">______________________________</div>
                    </td>
                </tr>
            </table>

            <div class="local-data">{{ $local_data ?? '' }}</div>
        </div>

        <div class="divisoria">
            <div class="linha-igual">{{ str_repeat('=', 200) }}</div>
        </div>

        {{-- ===================== VIA 2 (sem foto) ===================== --}}
        <div class="via via-2">
            <div class="escola-titulo">{{ $escola }}</div>

            <div class="header-simples">
                FICHA&nbsp;&nbsp;&nbsp;DE&nbsp;&nbsp;&nbsp;MATRÍCULA&nbsp;&nbsp;&nbsp;{{ $numero_processo ?: '_____' }}
            </div>

            <p class="texto-corrido">
                Processo nº {{ $numero_processo ?: '_____' }},
                idade {{ $idade ?: '___' }} anos,
                Sexo {{ $sexo ?: '_______' }},
                Nome {{ $nome ?: '_______________________' }},
                Classe {{ $classe ?: '_____' }},
                Turno da {{ $turno ?: '_______' }},
                Curso de {{ $curso ?: '_______________________' }}.
            </p>

            <table class="assinaturas">
                <tr>
                    <td>
                        <div class="rotulo">Encarregado(a) de Educação</div>
                        <div class="linha">______________________________</div>
                    </td>
                    <td>
                        <div class="rotulo">O Director(a)</div>
                        <div class="linha">______________________________</div>
                    </td>
                </tr>
            </table>

            <div class="local-data">{{ $local_data ?? '' }}</div>
        </div>

    </div>
</body>
</html>