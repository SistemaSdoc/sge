import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { CircleAlert, MailCheck } from 'lucide-react';
import { useEffect, useState } from 'react';
import { resend, verify } from '@/routes/tenant/dashboard/notes/two-factor';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp';
import { OTP_MAX_LENGTH } from '@/hooks/use-two-factor-auth';

export default function NotesTwoFactorChallenge({
  email,
  canResendAt,
  canVerify,
  sendError,
  sendErrorType,
  challengeExpired,
  scope,
}) {
  const [code, setCode] = useState('');
  const [now, setNow] = useState(Date.now());
  const waitToResend = Math.max(0, Math.ceil((canResendAt * 1000 - now) / 1000));
  const waitMinutes = Math.floor(waitToResend / 60);
  const waitSeconds = waitToResend % 60;
  const waitLabel = `${String(waitMinutes).padStart(2, '0')}:${String(waitSeconds).padStart(2, '0')}`;
  const hasSendError = Boolean(sendError);
  const isExpiredState = !canVerify && (challengeExpired || sendErrorType === 'limit');
  const alertVariant = hasSendError
    ? sendErrorType === 'limit' ? 'warning' : 'destructive'
    : challengeExpired ? 'warning' : 'info';
  const alertTitle = hasSendError
    ? sendErrorType === 'limit' ? 'Limite de envios atingido' : 'Código não enviado'
    : challengeExpired ? 'Código expirado' : canVerify ? 'Código enviado' : 'Acesso às notas';

  useEffect(() => {
    const timer = window.setInterval(() => setNow(Date.now()), 1000);

    return () => window.clearInterval(timer);
  }, []);

  setLayoutProps({
    title: 'Confirme o acesso às notas',
    description: 'Digite o código enviado ao seu e-mail para continuar.',
  });

  return (
    <>
      <Head title="Código enviado" />

      <div className="mx-auto w-full max-w-md space-y-5">
        {isExpiredState ? (
          <div className="space-y-5">
            <div className="space-y-2 px-4 py-3 text-center">
              <h2 className="text-sm font-medium text-foreground">
                {challengeExpired ? 'Código expirado' : 'Limite de envios atingido'}
              </h2>
              <p className="text-sm leading-relaxed text-muted-foreground">
                {sendErrorType === 'limit'
                  ? `O código para ${email} não foi enviado porque atingiu o limite de cinco envios.`
                  : 'O código anterior expirou. Solicite um novo para continuar.'}
              </p>
            </div>

            <Form {...resend.form()} options={{ preserveScroll: true }}>
              {({ errors, processing }) => (
                <div className="space-y-3 text-center">
                  <input type="hidden" name="scope" value={scope} />
                  <InputError message={errors.resend} />
                  {waitToResend > 0 ? (
                    <p className="text-sm text-muted-foreground" aria-live="polite">
                      Poderá pedir outro código em <span className="font-mono tabular-nums">{waitLabel}</span>.
                    </p>
                  ) : (
                    <Button type="submit" variant="link" disabled={processing}>
                      {processing ? 'Enviando...' : 'Pedir novo código por e-mail'}
                    </Button>
                  )}
                </div>
              )}
            </Form>
          </div>
        ) : (
          <>
            <div className="space-y-2 text-center">
              <h2 className="text-sm font-medium text-foreground">{alertTitle}</h2>
              <p className="text-sm leading-relaxed text-muted-foreground">
                {hasSendError ? (
                  sendError
                ) : (
                  <>
                    Enviámos um código para <strong>{email}</strong>. É válido por 10 minutos e só pode ser usado uma vez.
                  </>
                )}
              </p>
            </div>

            {canVerify && (
              <Form
                {...verify.form()}
                className="space-y-5"
                resetOnError
                resetOnSuccess
              >
                {({ errors, processing }) => (
                  <>
                    <div className="flex justify-center">
                      <InputOTP
                        name="code"
                        maxLength={OTP_MAX_LENGTH}
                        value={code}
                        onChange={setCode}
                        disabled={processing}
                        pattern={REGEXP_ONLY_DIGITS}
                        autoFocus
                      >
                        <InputOTPGroup>
                          {Array.from({ length: OTP_MAX_LENGTH }, (_, index) => (
                            <InputOTPSlot key={index} index={index} />
                          ))}
                        </InputOTPGroup>
                      </InputOTP>
                    </div>
                    <InputError message={errors.code} />
                    <Button
                      type="submit"
                      className="w-full"
                      disabled={processing || code.length !== OTP_MAX_LENGTH}
                    >
                      Confirmar
                    </Button>
                  </>
                )}
              </Form>
            )}

            <Form {...resend.form()} options={{ preserveScroll: true }}>
              {({ errors, processing }) => (
                <div className="space-y-3 text-center">
                  <input type="hidden" name="scope" value={scope} />
                  <InputError message={errors.resend} />
                  {waitToResend > 0 ? (
                    <p className="text-sm text-muted-foreground" aria-live="polite">
                      Novo envio disponível em <span className="font-mono tabular-nums">{waitLabel}</span>
                    </p>
                  ) : (
                    <Button type="submit" variant="link" disabled={processing}>
                      {processing ? 'Enviando...' : 'Reenviar código por e-mail'}
                    </Button>
                  )}
                </div>
              )}
            </Form>
          </>
        )}
      </div>
    </>
  );
}