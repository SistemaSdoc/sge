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
import { store } from '@/actions/App/Http/Controllers/Central/Auth/AuthenticatedSessionController';

export default function Login({ status, canResetPassword }) {
  /*const [googleLoading, setGoogleLoading] = useState(false);

  const handleGoogleConfirmation = () => {
    setGoogleLoading(true);
    window.location.href = redirect().url;
  };*/

  return (
    <>
      <Head title="Login" />

      <div className="flex flex-col gap-2">
        {/* <GoogleButton
          isLoading={googleLoading}
          onClick={handleGoogleConfirmation}
        /> */}
        <PasskeyVerify /*separator="Ou continue com email e senha"*/ />
      </div>

      <Form
        {...store.form()}
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
                  placeholder="email@example.com"
                />
                <InputError message={errors.email} />
              </div>

              <div className="grid gap-2">
                <div className="flex items-center">
                  <Label htmlFor="password">Senha</Label>
                  {/* {canResetPassword && (
                    <TextLink
                      href={request()}
                      className="ml-auto text-sm"
                      tabIndex={5}
                    >
                      Esqueceu sua senha?
                    </TextLink>
                  )}*/}
                </div>
                <PasswordInput
                  id="password"
                  name="password"
                  required
                  tabIndex={2}
                  autoComplete="current-password"
                  placeholder="Senha"
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
                Log in
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
  description: 'Insira suas credenciais para acessar sua conta',
};
