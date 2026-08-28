import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldSet,
} from '@/components/ui/field';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  InputGroup,
  InputGroupAddon,
  InputGroupInput,
} from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes/central';

export default function Register({ passwordRules }) {
  const { data, setData, post, processing, errors } = useForm({
    nome: '',
    sigla: '',
    tipo: '',
    domain: '',
    user_nome: '',
    user_email: '',
    password: '',
    password_confirmation: '',
  });

  const tipoOptions = [
    { value: 'colegio', label: 'Colégio' },
    { value: 'instituto', label: 'Instituto' },
  ];

  function handleSubmit(e) {
    e.preventDefault();
    post('/register');
  }

  return (
    <>
      <Head title="Register" />

      <form
        onSubmit={handleSubmit}
        className="flex w-full max-w-2xl flex-col gap-6"
      >
        <FieldGroup>
          <FieldSet>
            {/* Nome & Sigla */}

            <Field>
              <FieldLabel htmlFor="nome">Nome da Instituição</FieldLabel>
              <Input
                id="nome"
                type="text"
                required
                autoFocus
                placeholder="Ex.: Escola Secundária de Luanda"
                value={data.nome}
                onChange={(e) => setData('nome', e.target.value)}
              />
              {errors.nome && <FieldError>{errors.nome}</FieldError>}
            </Field>

            {/* Tipo & Subdomínio */}
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
              <Field>
                <FieldLabel htmlFor="tipo">Tipo da Instituição</FieldLabel>
                <Select
                  value={data.tipo}
                  onValueChange={(value) => setData('tipo', value)}
                >
                  <SelectTrigger className="w-full">
                    <SelectValue placeholder="Selecione o tipo" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      <SelectLabel>Tipo de instituição</SelectLabel>
                      {tipoOptions.map((opt) => (
                        <SelectItem key={opt.value} value={opt.value}>
                          {opt.label}
                        </SelectItem>
                      ))}
                    </SelectGroup>
                  </SelectContent>
                </Select>
                {errors.tipo && <FieldError>{errors.tipo}</FieldError>}
              </Field>

              <Field>
                <FieldLabel htmlFor="sigla">Sigla</FieldLabel>
                <Input
                  id="sigla"
                  type="text"
                  required
                  placeholder="Ex.: ESL"
                  value={data.sigla}
                  onChange={(e) =>
                    setData('sigla', e.target.value.toUpperCase())
                  }
                  maxLength="10"
                />
                {errors.sigla && <FieldError>{errors.sigla}</FieldError>}
              </Field>
            </div>

            <Field>
              <FieldLabel htmlFor="domain">Subdomínio</FieldLabel>
              <InputGroup>
                <InputGroupAddon className="font-normal text-foreground">
                  https://
                </InputGroupAddon>
                <InputGroupInput
                  id="domain"
                  type="text"
                  required
                  placeholder="Ex.: imcl"
                  value={data.domain}
                  onChange={(e) => setData('domain', e.target.value)}
                />
                <InputGroupAddon
                  align="inline-end"
                  className="font-normal text-foreground"
                >
                  .sge.localhost
                </InputGroupAddon>
              </InputGroup>
              {errors.domain && <FieldError>{errors.domain}</FieldError>}
            </Field>

            {/* Nome & Email do Diretor */}

            <Field>
              <FieldLabel htmlFor="user_nome">
                Nome do Utilizador (Diretor)
              </FieldLabel>
              <Input
                id="user_nome"
                type="text"
                required
                placeholder="Ex.: João da Silva"
                value={data.user_nome}
                onChange={(e) => setData('user_nome', e.target.value)}
              />
              {errors.user_nome && <FieldError>{errors.user_nome}</FieldError>}
            </Field>

            <Field>
              <FieldLabel htmlFor="user_email">
                Email do Utilizador (Diretor)
              </FieldLabel>
              <Input
                id="user_email"
                type="email"
                required
                placeholder="email@exemplo.com"
                value={data.user_email}
                onChange={(e) => setData('user_email', e.target.value)}
              />
              {errors.user_email && (
                <FieldError>{errors.user_email}</FieldError>
              )}
            </Field>

            {/* Senha */}
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
              <Field>
                <FieldLabel htmlFor="password">Senha</FieldLabel>
                <PasswordInput
                  id="password"
                  required
                  autoComplete="new-password"
                  placeholder="Senha"
                  value={data.password}
                  onChange={(e) => setData('password', e.target.value)}
                  passwordrules={passwordRules}
                />
                {errors.password && <FieldError>{errors.password}</FieldError>}
              </Field>

              <Field>
                <FieldLabel htmlFor="password_confirmation">
                  Confirmar Senha
                </FieldLabel>
                <PasswordInput
                  id="password_confirmation"
                  required
                  autoComplete="new-password"
                  placeholder="Confirmar senha"
                  value={data.password_confirmation}
                  onChange={(e) =>
                    setData('password_confirmation', e.target.value)
                  }
                  passwordrules={passwordRules}
                />
                {errors.password_confirmation && (
                  <FieldError>{errors.password_confirmation}</FieldError>
                )}
              </Field>
            </div>

            <Button type="submit" className="w-full" disabled={processing}>
              {processing && <Spinner />}
              Criar conta
            </Button>
          </FieldSet>
        </FieldGroup>

        {/* <div className="text-sm text-center text-muted-foreground">
          Já tem uma conta?{' '}
          <TextLink href={login().url}>Entrar</TextLink>
        </div> */}
      </form>
    </>
  );
}

Register.layout = {
  title: 'Criar uma conta',
  description: 'Insira seus dados abaixo para criar sua conta',
};
