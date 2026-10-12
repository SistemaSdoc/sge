import { Form, router } from '@inertiajs/react';
import { Link } from 'lucide-react';
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
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { GoogleIcon } from '@/components/google-icon';
import { FacebookIcon } from '@/components/facebook-icon';

export function SecurityData(props) {
  const passwordInput = useRef(null);
  const currentPasswordInput = useRef(null);

  const accounts = [
    {
      provider: 'google',
      name: 'Google',
      description: props.googleConnected ? 'Conectada' : 'Não conectada',
      connected: props.googleConnected,
      icon: <GoogleIcon aria-hidden="true" className="size-4" />,
      connect: () =>
        window.location.assign(
          SecurityController.beginGoogleLink.url({
            query: { return_to: window.location.pathname },
          }),
        ),
    },
    {
      provider: 'facebook',
      name: 'Facebook',
      description: props.facebookConnected ? 'Conectada' : 'Não conectada',
      connected: props.facebookConnected,
      icon: <FacebookIcon aria-hidden="true" className="size-4" />,
      connect: () =>
        window.location.assign(
          SecurityController.beginFacebookLink.url({
            query: { return_to: window.location.pathname },
          }),
        ),
    },
  ];

  const connectedAccounts = accounts.filter(
    (account) => account.connected,
  ).length;

  return (
    <div className="space-y-6">
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

      <div className="mx-auto w-full max-w-5xl">
        <Card className="gap-0 pb-0">
          <CardHeader className="border-b">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <CardTitle className="flex items-center gap-2">
                <Link
                  aria-hidden="true"
                  className="size-4 text-muted-foreground"
                />
                Contas conectadas
              </CardTitle>

              <span className="text-xs text-muted-foreground">
                {connectedAccounts}/2 conectadas
              </span>
            </div>

            <CardDescription>
              Associe as suas contas para iniciar sessão com Google ou Facebook.
            </CardDescription>
          </CardHeader>

          <CardContent className="p-0!">
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/50">
                  <TableHead className="px-4">Conta</TableHead>
                  <TableHead className="px-4 text-right">Acções</TableHead>
                </TableRow>
              </TableHeader>

              <TableBody>
                {accounts.map((account) => {
                  return (
                    <TableRow
                      key={account.name}
                      className="hover:bg-transparent"
                    >
                      <TableCell className="px-4">
                        <div className="flex items-center gap-1 md:gap-3">
                          <span className="flex size-9 items-center justify-center bg-background">
                            {account.icon}
                          </span>

                          <span className="flex flex-col">
                            <span className="font-medium">{account.name}</span>

                            <span className="text-xs text-muted-foreground">
                              {account.description}
                            </span>
                          </span>
                        </div>
                      </TableCell>

                      <TableCell className="px-4 text-right">
                        <Button
                          type="button"
                          size="xs"
                          variant="outline"
                          className="hover:cursor-pointer"
                          onClick={() => {
                            if (account.connected) {
                              router.delete(
                                SecurityController.disconnectSocialAccount.url(
                                  account.provider,
                                ),
                                { preserveScroll: true },
                              );

                              return;
                            }

                            account.connect();
                          }}
                        >
                          <Link />
                          {account.connected ? 'Desconectar' : 'Conectar'}
                        </Button>
                      </TableCell>
                    </TableRow>
                  );
                })}
              </TableBody>
            </Table>
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
    </div>
  );
}
