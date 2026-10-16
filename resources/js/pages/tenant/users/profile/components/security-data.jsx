import { Form } from '@inertiajs/react';
import { useRef } from 'react';
import SecurityController from '@/actions/App/Http/Controllers/Tenant/Settings/SecurityController';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
} from '@/components/ui/field';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';

export function SecurityData(props) {
  const passwordInput = useRef(null);
  const currentPasswordInput = useRef(null);

  return (
    <>
      <div className="mx-auto w-full max-w-5xl">
        <Card>
          <CardHeader>
            <CardTitle>
              {props.hasPassword ? 'Actualizar senha' : 'Definir senha'}
            </CardTitle>
            <CardDescription>
              {props.hasPassword
                ? 'Certifique-se de que sua conta esteja usando uma senha longa e aleatória para permanecer segura'
                : 'Defina uma senha forte para sua conta. Isso permitirá que você faça login com sua senha além de outras opções de autenticação.'}
            </CardDescription>
          </CardHeader>

          <CardContent>
            <Form
              {...SecurityController.update.form()}
              options={{ preserveScroll: true }}
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
              className="flex flex-col gap-6"
            >
              {({ errors, processing }) => (
                <>
                  <FieldGroup className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    {props.hasPassword && (
                      <Field
                        className="md:col-span-2"
                        data-invalid={Boolean(errors.current_password)}
                      >
                        <FieldLabel htmlFor="current_password">
                          Senha atual
                        </FieldLabel>

                        <PasswordInput
                          id="current_password"
                          ref={currentPasswordInput}
                          name="current_password"
                          autoComplete="current-password"
                          placeholder="Senha atual"
                          aria-invalid={Boolean(errors.current_password)}
                        />
                        <FieldError>{errors.current_password}</FieldError>
                      </Field>
                    )}

                    <Field data-invalid={Boolean(errors.password)}>
                      <FieldLabel htmlFor="password">
                        {props.hasPassword ? 'Nova senha' : 'Senha'}
                      </FieldLabel>
                      <PasswordInput
                        id="password"
                        ref={passwordInput}
                        name="password"
                        autoComplete="new-password"
                        placeholder={props.hasPassword ? 'Nova senha' : 'Senha'}
                        passwordrules={props.passwordRules}
                        aria-invalid={Boolean(errors.password)}
                      />
                      <FieldError>{errors.password}</FieldError>
                    </Field>

                    <Field data-invalid={Boolean(errors.password_confirmation)}>
                      <FieldLabel htmlFor="password_confirmation">
                        Confirmar senha
                      </FieldLabel>
                      <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        autoComplete="new-password"
                        placeholder="Confirmar senha"
                        passwordrules={props.passwordRules}
                        aria-invalid={Boolean(errors.password_confirmation)}
                      />
                      <FieldError>{errors.password_confirmation}</FieldError>
                    </Field>
                  </FieldGroup>

                  <div className="flex justify-end">
                    <Button
                      disabled={processing}
                      className="hover:cursor-pointer"
                    >
                      Salvar
                    </Button>
                  </div>
                </>
              )}
            </Form>
          </CardContent>
        </Card>
      </div>

      {/* <ManageTwoFactor
        canManageTwoFactor={props.canManageTwoFactor}
        requiresConfirmation={props.requiresConfirmation}
        twoFactorEnabled={props.twoFactorEnabled}
      /> */}

      {/* <ManagePasskeys
        canManagePasskeys={props.canManagePasskeys}
        passkeys={props.passkeys}
      /> */}
    </>
  );
}
