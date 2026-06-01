import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import {
  index as confirmOptions,
  store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import { GoogleButton } from '@/components/google-button';
import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/password/confirm';
import { redirect } from '@/actions/App/Http/Controllers/Auth/PasswordConfirmationGoogleController';

export default function ConfirmPassword() {
  const [googleLoading, setGoogleLoading] = useState(false);

  const handleGoogleConfirmation = () => {
    setGoogleLoading(true);
    window.location.href = redirect().url;
  };

  return (
    <>
      <Head title="Confirmar senha" />

      <PasskeyVerify
        routes={{
          options: confirmOptions(),
          submit: confirmStore(),
        }}
        label="Confirmar com uma passkey"
        loadingLabel="Confirming..."
        separator="Ou, confirme com Google"
      />

      <div className="space-y-6">
        <GoogleButton
          isLoading={googleLoading}
          onClick={handleGoogleConfirmation}
          disabled={googleLoading}
        />
      </div>

      <Form {...store.form()} resetOnSuccess={['password']}>
        {({ processing, errors }) => (
          <div className="space-y-6">
            <div className="relative">
              <div className="absolute inset-0 flex items-center">
                <span className="w-full border-t border-gray-300" />
              </div>
              <div className="relative flex justify-center text-sm">
                <span className="bg-white px-2 text-gray-500">
                  Ou, confirme com sua senha
                </span>
              </div>
            </div>

            <div className="grid gap-2">
              <Label htmlFor="password">Senha</Label>
              <PasswordInput
                id="password"
                name="password"
                placeholder="Senha"
                autoComplete="current-password"
                autoFocus
              />

              <InputError message={errors.password} />
            </div>

            <div className="flex items-center">
              <Button
                className="w-full"
                disabled={processing}
                data-test="confirm-password-button"
              >
                {processing && <Spinner />}
                Confirmar senha
              </Button>
            </div>
          </div>
        )}
      </Form>
    </>
  );
}

ConfirmPassword.layout = {
  title: 'Confirmar senha',
  description:
    'Por favor, confirme sua senha antes de continuar. Você pode confirmar usando uma passkey, Google ou digitando sua senha.',
};
