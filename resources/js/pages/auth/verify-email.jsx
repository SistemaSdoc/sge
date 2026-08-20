// Components
import { Form, Head } from '@inertiajs/react';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes/tenant';
import { send } from '@/routes/verification';

export default function VerifyEmail({ status }) {
  return (
    <>
      <Head title="Verificação de email" />

      {status === 'verification-link-sent' && (
        <div className="mb-4 text-center text-sm font-medium text-green-600">
          Um novo link de verificação foi enviado para o seu endereço de email.
          Por favor, verifique seu email para obter o link de verificação.
        </div>
      )}

      <Form {...send.form()} className="space-y-6 text-center">
        {({ processing }) => (
          <>
            <Button disabled={processing} variant="secondary">
              {processing && <Spinner />}
              Reenviar email de verificação
            </Button>

            <TextLink href={logout()} className="mx-auto block text-sm">
              Sair
            </TextLink>
          </>
        )}
      </Form>
    </>
  );
}

VerifyEmail.layout = {
  title: 'Verificação de email',
  description:
    'Por favor, verifique seu endereço de email clicando no link que enviamos para você.',
};
