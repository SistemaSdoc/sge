<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html dir="ltr" lang="pt">
<head>
    <meta content="text/html; charset=UTF-8" http-equiv="Content-Type" />
    <meta name="x-apple-disable-message-reformatting" />
</head>
<body style="background-color:rgb(255,255,255);margin:0;padding:0">
    <table border="0" width="100%" cellpadding="0" cellspacing="0" role="presentation" align="center">
        <tbody>
            <tr>
                <td style='margin:auto;background-color:rgb(255,255,255);padding:0 0.5rem;font-family:ui-sans-serif,system-ui,sans-serif'>
                    <table align="center" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation"
                        style="max-width:30rem;margin:40px auto;border-radius:0.25rem;border:1px solid rgb(234,234,234);padding:20px">
                        <tbody>
                            <tr style="width:100%">
                                <td>

                                    <table align="center" width="100%" border="0" cellpadding="0" cellspacing="0"
                                        role="presentation" style="margin-top:32px;text-align:center">
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <h1 style="margin:20px 0;text-align:center;font-size:24px;font-weight:400;color:rgb(0,0,0)">
                                                        Pagamento registado
                                                    </h1>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <p style="font-size:14px;line-height:24px;color:rgb(0,0,0);margin:16px 0">
                                        Olá, <strong>{{ $nome }}</strong>!
                                    </p>

                                    <p style="font-size:14px;line-height:24px;color:rgb(0,0,0);margin:16px 0">
                                        O seu pagamento foi registado com sucesso. Abaixo o resumo:
                                    </p>

                                    <hr style="width:100%;border:none;border-top:1px solid #eaeaea;margin:26px 0" />

                                    <table align="center" width="100%" border="0" cellpadding="0" cellspacing="0"
                                        role="presentation"
                                        style="border-radius:0.25rem;background-color:rgb(244,244,245);padding:12px 16px">
                                        <tbody>
                                            <tr>
                                                <td>
                                                    @if($numeroRecibo)
                                                    <p style="font-size:13px;line-height:24px;margin:0;color:rgb(102,102,102)">Nº Recibo</p>
                                                    <p style="font-size:14px;line-height:24px;margin:0 0 12px;font-weight:600;color:rgb(0,0,0)">{{ $numeroRecibo }}</p>
                                                    @endif

                                                    <p style="font-size:13px;line-height:24px;margin:0;color:rgb(102,102,102)">Valor pago</p>
                                                    <p style="font-size:14px;line-height:24px;margin:0 0 12px;font-weight:600;color:rgb(0,0,0)">{{ $valorTotal }} AOA</p>

                                                    <p style="font-size:13px;line-height:24px;margin:0;color:rgb(102,102,102)">Data</p>
                                                    <p style="font-size:14px;line-height:24px;margin:0 0 12px;font-weight:600;color:rgb(0,0,0)">{{ $dataPagamento }}</p>

                                                    <p style="font-size:13px;line-height:24px;margin:0;color:rgb(102,102,102)">Método</p>
                                                    <p style="font-size:14px;line-height:24px;margin:0 0 12px;font-weight:600;color:rgb(0,0,0)">{{ $metodo }}</p>

                                                    @if($referencia)
                                                    <p style="font-size:13px;line-height:24px;margin:0;color:rgb(102,102,102)">Referência</p>
                                                    <p style="font-size:14px;line-height:24px;margin:0;font-weight:600;color:rgb(0,0,0)">{{ $referencia }}</p>
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <hr style="width:100%;border:none;border-top:1px solid #eaeaea;margin:26px 0" />

                                    <p style="font-size:12px;line-height:24px;color:rgb(102,102,102);margin:16px 0">
                                        Este email foi gerado automaticamente. Por favor, não responda directamente a esta mensagem.
                                    </p>

                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>