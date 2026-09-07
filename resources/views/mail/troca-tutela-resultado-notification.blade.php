<!DOCTYPE html>
<html dir="ltr" lang="pt">

<head>
  <meta charset="UTF-8">
  <meta name="x-apple-disable-message-reformatting">
</head>

<body style="background-color:#fff;margin:0;padding:0">
  <table border="0" width="100%" cellpadding="0" cellspacing="0" role="presentation" align="center">
    <tbody>
      <tr>
        <td style="margin:auto;background-color:#fff;padding:0 0.5rem;font-family:ui-sans-serif,system-ui,sans-serif">
          <table align="center" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation"
            style="max-width:30rem;margin:40px auto;border:1px solid #eaeaea;border-radius:0.25rem;padding:20px">
            <tbody>
              <tr>
                <td>
                  <h1 style="margin:20px 0;text-align:center;font-size:24px;font-weight:400;color:#000">
                    @if ($resultado === 'pendente')
                      Troca de tutela pendente
                    @elseif ($fase === 'instituicao_anterior')
                      Troca de tutela aprovada
                    @elseif ($resultado === 'aprovada')
                      Tutela aceite
                    @else
                      Tutela rejeitada
                    @endif
                  </h1>
                  <p style="font-size:14px;line-height:24px;color:#000;margin:16px 0">
                    Olá!
                  </p>
                  @if ($resultado === 'pendente')
                    <p style="font-size:14px;line-height:24px;color:#000;margin:16px 0">
                      A troca de tutela do curso <strong>{{ $nomeCurso }}</strong> para o
                      <strong>{{ $nomeInstituicaoProposta }}</strong> foi registada.
                    </p>
                    <p style="font-size:14px;line-height:24px;color:#000;margin:16px 0">
                      O pedido aguarda a aprovação da instituição tutora actual,
                      <strong>{{ $nomeInstituicaoDecisora }}</strong>.
                    </p>
                  @elseif ($fase === 'instituicao_anterior')
                    <p style="font-size:14px;line-height:24px;color:#000;margin:16px 0">
                      A instituição tutora actual, <strong>{{ $nomeInstituicaoDecisora }}</strong>, aprovou a troca de tutela do curso
                      <strong>{{ $nomeCurso }}</strong> para o
                      <strong>{{ $nomeInstituicaoProposta }}</strong>.
                    </p>
                    <p style="font-size:14px;line-height:24px;color:#000;margin:16px 0">
                      A instituição proposta ainda precisa aceitar a tutela para concluir a troca.
                    </p>
                  @elseif ($resultado === 'aprovada')
                    <p style="font-size:14px;line-height:24px;color:#000;margin:16px 0">
                      O <strong>{{ $nomeInstituicaoDecisora }}</strong> aceitou assumir a tutela do curso
                      <strong>{{ $nomeCurso }}</strong>. A troca de tutela foi concluída.
                    </p>
                  @else
                    <p style="font-size:14px;line-height:24px;color:#000;margin:16px 0">
                      O <strong>{{ $nomeInstituicaoDecisora }}</strong> rejeitou assumir a tutela do curso
                      <strong>{{ $nomeCurso }}</strong>. A tutela anterior permanece activa.
                    </p>
                  @endif
                  <hr style="width:100%;border:none;border-top:1px solid #eaeaea;margin:26px 0">
                  <table align="center" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation"
                    style="margin:32px 0;text-align:center">
                    <tbody>
                      <tr>
                        <td>
                          <a href="{{ $url }}" target="_blank"
                            style="display:inline-block;border-radius:0.25rem;background-color:#000;padding:12px 20px;text-align:center;font-size:12px;font-weight:600;color:#fff;text-decoration:none">
                            Ver detalhes
                          </a>
                        </td>
                      </tr>
                    </tbody>
                  </table>
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
