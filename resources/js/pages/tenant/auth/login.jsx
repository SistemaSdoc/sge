import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import { GoogleButton } from '@/components/google-button';
import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store as LoginWithEmailAndPassword } from '@/actions/App/Http/Controllers/Tenant/Auth/AuthenticatedSessionController';
import { request } from '@/routes/password';
//import { redirect as LoginWithGoogle } from '@/actions/App/Http/Controllers/Tenant/Auth/GoogleAuthController';

export default function Login({ status, canResetPassword }) {
  /*const [googleLoading, setGoogleLoading] = useState(false);

  const handleGoogleConfirmation = () => {
    setGoogleLoading(true);
    window.location.href = LoginWithGoogle().url;
  };*/

  return (
    <>
      <Head title="Iniciar sessão" />

      {/*<div className="flex flex-col gap-2">
        <GoogleButton
          isLoading={googleLoading}
          onClick={handleGoogleConfirmation}
        />
        <PasskeyVerify /*separator="Ou continue com email e senha" />
      </div>*/}

      <Form
        {...LoginWithEmailAndPassword.post()}
        resetOnSuccess={['password']}
        className="flex flex-col gap-6"
      >
        {({ processing, errors }) => (
          <>
            <div className="grid gap-6">
              <div className="grid gap-2">
                <Label htmlFor="email">Endereço de Email</Label>
                <Input
                  id="email"
                  type="email"
                  name="email"
                  required
                  autoFocus
                  tabIndex={1}
                  autoComplete="email"
                  placeholder="email@exemplo.com"
                />
                <InputError message={errors.email} />
              </div>

              <div className="grid gap-2">
                <div className="flex items-center">
                  <Label htmlFor="password">Palavra-passe</Label>
                  {canResetPassword && (
                    <TextLink
                      href={request().url}
                      className="ml-auto text-sm"
                      tabIndex={5}
                    >
                      Esqueceu-se da palavra-passe?
                    </TextLink>
                  )}
                </div>
                <PasswordInput
                  id="password"
                  name="password"
                  required
                  tabIndex={2}
                  autoComplete="current-password"
                  placeholder="Palavra-passe"
                />
                <InputError message={errors.password} />
              </div>

              <div className="flex items-center space-x-3">
                <Checkbox id="remember" name="remember" tabIndex={3} />
                <Label htmlFor="remember">Lembrar-me</Label>
              </div>

              <Button
                type="submit"
                className="mt-4 w-full"
                tabIndex={4}
                disabled={processing}
                data-test="login-button"
              >
                {processing && <Spinner />}
                Iniciar sessão
              </Button>
            </div>

            {/* <div className="text-center text-sm text-muted-foreground">
              Não tem uma conta?{' '}
              <TextLink href={register()} tabIndex={5}>
                Criar
              </TextLink>
            </div>*/}
          </>
        )}
      </Form>

      {status && (
        <div className="mb-4 text-center text-sm font-medium text-green-600">
          {status}
        </div>
      )}
    </>
  );
}

Login.layout = {
  title: 'Inicie sessão na sua conta',
  description: 'Introduza as suas credenciais para aceder à sua conta.',
};
