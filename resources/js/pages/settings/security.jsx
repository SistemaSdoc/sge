import { Form, Head } from '@inertiajs/react';
import { useRef } from 'react';
import SecurityController from '@/actions/App/Http/Controllers/Tenant/Settings/SecurityController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import ManagePasskeys from '@/components/manage-passkeys';
import ManageTwoFactor from '@/components/manage-two-factor';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/security';

export default function Security(props) {
  const passwordInput = useRef(null);
  const currentPasswordInput = useRef(null);

  return (
    <>
      <Head title="Configurações de segurança" />

      <h1 className="sr-only">Configurações de segurança</h1>

      <div className="space-y-6">
        <Heading
          variant="small"
          title={props.hasPassword ? 'Actualizar senha' : 'Definir senha'}
          description={
            props.hasPassword
              ? 'Certifique-se de que sua conta esteja usando uma senha longa e aleatória para permanecer segura'
              : 'Defina uma senha forte para sua conta. Isso permitirá que você faça login com sua senha além de outras opções de autenticação.'
          }
        />

        <Form
          {...SecurityController.update.form()}
          options={{
            preserveScroll: true,
          }}
          resetOnError={
            props.hasPassword
              ? ['password', 'password_confirmation', 'current_password']
              : ['password', 'password_confirmation']
          }
          resetOnSuccess
          onError={(errors) => {
            if (errors.password) {
              passwordInput.current?.focus();
            }

            if (errors.current_password) {
              currentPasswordInput.current?.focus();
            }
          }}
          className="space-y-6"
        >
          {({ errors, processing }) => (
            <>
              {props.hasPassword && (
                <div className="grid gap-2">
                  <Label htmlFor="current_password">Senha atual</Label>

                  <PasswordInput
                    id="current_password"
                    ref={currentPasswordInput}
                    name="current_password"
                    className="mt-1 block w-full"
                    autoComplete="current-password"
                    placeholder="Senha atual"
                  />

                  <InputError message={errors.current_password} />
                </div>
              )}

              <div className="grid gap-2">
                <Label htmlFor="password">
                  {props.hasPassword ? 'Nova senha' : 'Senha'}
                </Label>

                <PasswordInput
                  id="password"
                  ref={passwordInput}
                  name="password"
                  className="mt-1 block w-full"
                  autoComplete="new-password"
                  placeholder={props.hasPassword ? 'Nova senha' : 'Senha'}
                  passwordrules={props.passwordRules}
                />

                <InputError message={errors.password} />
              </div>

              <div className="grid gap-2">
                <Label htmlFor="password_confirmation">Confirmar senha</Label>

                <PasswordInput
                  id="password_confirmation"
                  name="password_confirmation"
                  className="mt-1 block w-full"
                  autoComplete="new-password"
                  placeholder="Confirmar senha"
                  passwordrules={props.passwordRules}
                />

                <InputError message={errors.password_confirmation} />
              </div>

              <div className="flex items-center gap-4">
                <Button
                  disabled={processing}
                  data-test="update-password-button"
                >
                  Salvar
                </Button>
              </div>
            </>
          )}
        </Form>
      </div>

      <ManageTwoFactor
        canManageTwoFactor={props.canManageTwoFactor}
        requiresConfirmation={props.requiresConfirmation}
        twoFactorEnabled={props.twoFactorEnabled}
      />

      <ManagePasskeys
        canManagePasskeys={props.canManagePasskeys}
        passkeys={props.passkeys}
      />
    </>
  );
}

Security.layout = {
  breadcrumbs: [
    {
      title: 'Configurações de segurança',
      href: edit(),
    },
  ],
};
