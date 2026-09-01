<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html dir="ltr" lang="pt">

<head>
    <meta content="text/html; charset=UTF-8" http-equiv="Content-Type" />
    <meta name="x-apple-disable-message-reformatting" />
</head>

<body
    style="background-color:rgb(255,255,255);margin-top:0;margin-bottom:0;margin-right:0;margin-left:0;padding-right:0;padding-left:0">

```
<!-- Preheader -->
<div
    style="display:none;overflow:hidden;line-height:1px;opacity:0;max-height:0;max-width:0"
    data-skip-in-text="true">
    Foram solicitadas melhorias no tema do vosso grupo PAP.
</div>

<!-- Body -->
<table border="0" width="100%" cellpadding="0" cellspacing="0" role="presentation" align="center">
    <tbody>
        <tr>
            <td
                style='margin-right:auto;margin-left:auto;margin-bottom:auto;margin-top:auto;background-color:rgb(255,255,255);padding-right:0.5rem;padding-left:0.5rem;font-family:ui-sans-serif,system-ui,sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji"'>

                <table
                    align="center"
                    width="100%"
                    border="0"
                    cellpadding="0"
                    cellspacing="0"
                    role="presentation"
                    style="max-width:30rem;margin-right:auto;margin-left:auto;margin-bottom:40px;margin-top:40px;border-radius:0.25rem;border-style:solid;border-width:1px;border-color:rgb(234,234,234);padding:20px">

                    <tbody>
                        <tr style="width:100%">
                            <td>

                                <!-- Título -->
                                <table
                                    align="center"
                                    width="100%"
                                    border="0"
                                    cellpadding="0"
                                    cellspacing="0"
                                    role="presentation"
                                    style="margin-top:32px;text-align:center">

                                    <tbody>
                                        <tr>
                                            <td>
                                                <h1
                                                    style="margin-right:0;margin-left:0;margin-bottom:20px;margin-top:20px;padding:0;text-align:center;font-size:24px;font-weight:400;color:rgb(0,0,0)">
                                                    Melhorias solicitadas no tema PAP
                                                </h1>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <!-- Saudação -->
                                <p
                                    style="font-size:14px;line-height:24px;color:rgb(0,0,0);margin-top:16px;margin-bottom:16px">
                                    Olá,
                                </p>

                                <!-- Mensagem principal -->
                                <p
                                    style="font-size:14px;line-height:24px;color:rgb(0,0,0);margin-top:16px;margin-bottom:16px">
                                    O <strong>{{ $solicitadoPor }}</strong> solicitou melhorias no tema do vosso
                                    grupo PAP.
                                </p>

                                <!-- Separador -->
                                <hr
                                    style="width:100%;border:none;border-top:1px solid #eaeaea;margin-right:0;margin-left:0;margin-bottom:26px;margin-top:26px;border-style:solid;border-width:1px;border-color:rgb(234,234,234)" />

                                <!-- Informações do grupo -->
                                <table
                                    align="center"
                                    width="100%"
                                    border="0"
                                    cellpadding="0"
                                    cellspacing="0"
                                    role="presentation"
                                    style="border-radius:0.25rem;background-color:rgb(244,244,245);padding:12px 16px">

                                    <tbody>
                                        <tr>
                                            <td>
                                                <p
                                                    style="font-size:13px;line-height:24px;margin-top:0;margin-bottom:0;color:rgb(102,102,102)">
                                                    Grupo
                                                </p>

                                                <p
                                                    style="font-size:14px;line-height:24px;margin-top:0;margin-bottom:0;font-weight:600;color:rgb(0,0,0)">
                                                    {{ $nomeGrupo }}
                                                </p>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td style="padding-top:8px">
                                                <p
                                                    style="font-size:13px;line-height:24px;margin-top:0;margin-bottom:0;color:rgb(102,102,102)">
                                                    Tema
                                                </p>

                                                <p
                                                    style="font-size:14px;line-height:24px;margin-top:0;margin-bottom:0;font-weight:600;color:rgb(0,0,0)">
                                                    {{ $temaGrupo }}
                                                </p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <!-- Feedback -->
                                @if($comentario)

                                <p
                                    style="font-size:14px;line-height:24px;font-weight:600;color:rgb(0,0,0);margin-top:16px;margin-bottom:16px">
                                    Feedback:
                                </p>

                                <table
                                    align="center"
                                    width="100%"
                                    border="0"
                                    cellpadding="0"
                                    cellspacing="0"
                                    role="presentation"
                                    style="border-radius:0.25rem;background-color:rgb(254,249,236);border-left:3px solid rgb(217,119,6);padding:12px 16px">

                                    <tbody>
                                        <tr>
                                            <td>
                                                <p
                                                    style="font-size:14px;line-height:24px;margin-top:0;margin-bottom:0;color:rgb(0,0,0)">
                                                    {{ $comentario }}
                                                </p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                @endif

                                <!-- Instrução -->
                                <p
                                    style="font-size:14px;line-height:24px;color:rgb(0,0,0);margin-top:16px;margin-bottom:16px">
                                    Por favor, revejam o feedback, façam as alterações necessárias e reenviem o
                                    tema para aprovação.
                                </p>

                                <!-- Separador -->
                                <hr
                                    style="width:100%;border:none;border-top:1px solid #eaeaea;margin-right:0;margin-left:0;margin-bottom:26px;margin-top:26px;border-style:solid;border-width:1px;border-color:rgb(234,234,234)" />

                                <!-- Botão -->
                                <table
                                    align="center"
                                    width="100%"
                                    border="0"
                                    cellpadding="0"
                                    cellspacing="0"
                                    role="presentation"
                                    style="margin-top:32px;margin-bottom:32px;text-align:center">

                                    <tbody>
                                        <tr>
                                            <td>

                                                <a
                                                    href="{{ $url }}"
                                                    style="line-height:100%;text-decoration:none;display:inline-block;max-width:100%;mso-padding-alt:0px;border-radius:0.25rem;background-color:rgb(0,0,0);padding-right:20px;padding-left:20px;padding-bottom:12px;padding-top:12px;text-align:center;font-size:12px;font-weight:600;color:rgb(255,255,255);text-decoration-line:none"
                                                    target="_blank">

                                                    <span>
                                                        <!--[if mso]>
                                                        <i style="mso-font-width:500%;mso-text-raise:18" hidden>
                                                            &#8202;&#8202;
                                                        </i>
                                                        <![endif]-->
                                                    </span>

                                                    <span
                                                        style="max-width:100%;display:inline-block;line-height:120%;mso-padding-alt:0px;mso-text-raise:9px">
                                                        Ver grupo PAP
                                                    </span>

                                                    <span>
                                                        <!--[if mso]>
                                                        <i style="mso-font-width:500%" hidden>
                                                            &#8202;&#8202;&#8203;
                                                        </i>
                                                        <![endif]-->
                                                    </span>

                                                </a>

                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <!-- Link alternativo -->
                                <p
                                    style="font-size:14px;line-height:24px;color:rgb(0,0,0);margin-top:16px;margin-bottom:16px">

                                    ou copie e cole este link no seu navegador:

                                    <a
                                        href="{{ $url }}"
                                        style="color:rgb(21,93,252);text-decoration-line:none"
                                        target="_blank">
                                        {{ $url }}
                                    </a>

                                </p>

                                <!-- Separador -->
                                <hr
                                    style="width:100%;border:none;border-top:1px solid #eaeaea;margin-right:0;margin-left:0;margin-bottom:26px;margin-top:26px;border-style:solid;border-width:1px;border-color:rgb(234,234,234)" />

                                <!-- Rodapé -->
                                <p
                                    style="font-size:12px;line-height:24px;color:rgb(102,102,102);margin-top:16px;margin-bottom:16px">
                                    Este email foi gerado automaticamente. Por favor, não responda directamente a
                                    esta mensagem.
                                </p>

                            </td>
                        </tr>
                    </tbody>

                </table>

            </td>
        </tr>
    </tbody>
</table>
```

</body>

</html>
