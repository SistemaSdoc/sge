import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Field,
  FieldDescription,
  FieldGroup,
  FieldLabel,
  FieldSeparator,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { store as LoginWithEmailAndPassword } from '@/actions/App/Http/Controllers/Tenant/Auth/AuthenticatedSessionController';
import { redirect as googleRedirect } from '@/actions/App/Http/Controllers/Central/Auth/GoogleAuthController';
import { request } from '@/routes/password';
import { GoogleButton } from './components/socials-buttons/google-button';
import { AppleButton } from './components/socials-buttons/apple-button';
import { FacebookButton } from './components/socials-buttons/facebook-button';

export default function Login({ status, canResetPassword }) {
  return (
    <>
      <Head title="Login" />

      <Form
        {...LoginWithEmailAndPassword.post()}
        resetOnSuccess={['password']}
        className="w-full"
      >
        {({ processing, errors }) => (
          <FieldGroup>
            <div className="flex flex-col gap-1 text-center">
              <h1 className="text-lg font-bold md:text-xl">
                Inicie sessão na sua conta
              </h1>
              <p className="text-sm text-muted-foreground">
                Introduza o seu email e a sua senha para aceder à sua conta.
              </p>
            </div>

            <Field data-invalid={Boolean(errors.email)}>
              <FieldLabel htmlFor="email">Email</FieldLabel>
              <Input
                id="email"
                type="email"
                name="email"
                required
                autoFocus
                tabIndex={1}
                autoComplete="email"
                placeholder="email@exemplo.ao"
                aria-invalid={Boolean(errors.email)}
              />
              <InputError message={errors.email} />
            </Field>

            <Field data-invalid={Boolean(errors.password)}>
              <div className="flex items-center gap-3">
                <FieldLabel htmlFor="password">Senha</FieldLabel>
                {canResetPassword && (
                  <Link
                    href={request().url}
                    className="ml-auto text-xs text-muted-foreground hover:underline"
                    tabIndex={5}
                  >
                    Esqueceu a senha?
                  </Link>
                )}
              </div>

              <PasswordInput
                id="password"
                name="password"
                required
                tabIndex={2}
                autoComplete="current-password"
                placeholder="Insira a sua senha"
                aria-invalid={Boolean(errors.password)}
              />
              <InputError message={errors.password} />
            </Field>

            <Field orientation="horizontal" className="items-center">
              <Checkbox id="remember" name="remember" tabIndex={3} />
              <FieldLabel htmlFor="remember">Lembrar-me</FieldLabel>
            </Field>

            <Field>
              <Button
                type="submit"
                className="w-full hover:cursor-pointer"
                tabIndex={4}
                disabled={processing}
                data-test="login-button"
              >
                {processing && <Spinner data-icon="inline-start" />}
                Entrar
              </Button>
            </Field>

            <FieldSeparator className="my-1 *:data-[slot=field-separator-content]:bg-background">
              Ou continue com
            </FieldSeparator>

            <Field>
              <GoogleButton
                onClick={() => {
                  const target = `${googleRedirect.url()}?tenant=${encodeURIComponent(window.location.origin)}`;
                  window.location.assign(target);
                }}
              />
              <FacebookButton />
            </Field>
          </FieldGroup>
        )}
      </Form>

      {status && (
        <FieldDescription
          role="status"
          className="mt-4 text-center text-primary"
        >
          {status}
        </FieldDescription>
      )}
    </>
  );
}

Login.layout = {
  showAside: false,
  formFooter: (
    <FieldDescription className="text-center">
      Precisa de acesso? Contacte a administração da sua instituição.
    </FieldDescription>
  ),
};
