import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import { GoogleButton } from '@/components/google-button';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes/central';
import { store } from '@/routes/central/register';

export default function Register({ passwordRules }) {
  return (
    <>
      <Head title="Register" />

      <Form
        action="/register"
        method="post"
        resetOnSuccess={['password', 'password_confirmation']}
        disableWhileProcessing
        className="flex flex-col gap-2"
      >
        {({ processing, errors }) => (
          <>
            <div className="grid gap-6">
              <div className="grid gap-2">
                <Label htmlFor="nome">Nome</Label>
                <Input
                  id="nome"
                  type="text"
                  required
                  autoFocus
                  tabIndex={1}
                  autoComplete="nome"
                  name="nome"
                  placeholder="Nome completo"
                />
                <InputError message={errors.nome} className="mt-2" />
              </div>

              <div className="grid gap-2">
                <Label htmlFor="email">Endereço de Email</Label>
                <Input
                  id="email"
                  type="email"
                  required
                  tabIndex={2}
                  autoComplete="email"
                  name="email"
                  placeholder="email@exemplo.com"
                />
                <InputError message={errors.email} />
              </div>

              <div className="grid gap-2">
                <Label htmlFor="password">Senha</Label>
                <PasswordInput
                  id="password"
                  required
                  tabIndex={3}
                  autoComplete="new-password"
                  name="password"
                  placeholder="Senha"
                  passwordrules={passwordRules}
                />
                <InputError message={errors.password} />
              </div>

              <div className="grid gap-2">
                <Label htmlFor="password_confirmation">Confirmar senha</Label>
                <PasswordInput
                  id="password_confirmation"
                  required
                  tabIndex={4}
                  autoComplete="new-password"
                  name="password_confirmation"
                  placeholder="Confirmar senha"
                  passwordrules={passwordRules}
                />
                <InputError message={errors.password_confirmation} />
              </div>

              <div className="grid grid-cols-2 gap-2">
                <div className="space-y-2">
                  <Label htmlFor="tenant_name">Nome da instituição</Label>
                  <Input
                    id="tenant_name"
                    required
                    tabIndex={4}
                    autoComplete="tenant_name"
                    name="tenant_name"
                    placeholder="Nome da instituição"
                  />
                  <InputError message={errors.tenant_name} />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="domain">Domínio</Label>
                  <Input
                    id="domain"
                    required
                    tabIndex={4}
                    autoComplete="domain"
                    name="domain"
                    placeholder="sdoca"
                  />
                  <InputError message={errors.domain} />
                </div>
              </div>

              <Button
                type="submit"
                className="mt-2 w-full"
                tabIndex={5}
                data-test="register-user-button"
              >
                {processing && <Spinner />}
                Criar conta
              </Button>
            </div>

            <div className="text-center text-sm text-muted-foreground">
              Já tem uma conta?{' '}
              <TextLink href={login().url} tabIndex={6}>
                Entrar
              </TextLink>
            </div>
          </>
        )}
      </Form>
    </>
  );
}

Register.layout = {
  title: 'Criar uma conta',
  description:
    'Insira seus dados abaixo para criar sua conta ou continue com Google',
};
