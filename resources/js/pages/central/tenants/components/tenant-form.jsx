'use client';

import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldSet,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectLabel,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { ButtonGroup } from '@/components/ui/button-group';

export function TenantForm({
  title,
  data,
  setData,
  errors,
  processing,
  submitFn,
  submitLabel = 'Guardar',
  can = {},
}) {
  const canSubmit = Boolean(can.create ?? true);

  const tipoOptions = [
    { value: 'colegio', label: 'Colégio' },
    { value: 'instituto', label: 'Instituto' },
  ];

  return (
    <div className="w-full max-w-sm px-6 py-6 mx-auto md:max-w-md lg:max-w-2xl">
      <form onSubmit={submitFn}>
        <Card className="overflow-visible">
          <CardHeader className="border-b">
            <CardTitle>{title}</CardTitle>
          </CardHeader>

          <CardContent>
            <FieldGroup>
              <FieldSet>
                {/* Nome & Email do User */}
                <div className="grid grid-cols-1 gap-4 pt-6 mt-6 border-t md:grid-cols-2">
                  <Field>
                    <FieldLabel htmlFor="user_nome">Nome do usuário</FieldLabel>
                    <Input
                      id="user_nome"
                      type="text"
                      placeholder="Ex.: João Silva"
                      value={data.user_nome}
                      onChange={(e) => setData('user_nome', e.target.value)}
                    />
                    {errors.user_nome && (
                      <FieldError>{errors.user_nome}</FieldError>
                    )}
                  </Field>

                  <Field>
                    <FieldLabel htmlFor="user_email">
                      Email do usuário
                    </FieldLabel>
                    <Input
                      id="user_email"
                      type="email"
                      placeholder="Ex.: director@escola.ao"
                      value={data.user_email}
                      onChange={(e) => setData('user_email', e.target.value)}
                    />
                    {errors.user_email && (
                      <FieldError>{errors.user_email}</FieldError>
                    )}
                  </Field>
                </div>

                {/* Nome & Sigla */}
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <Field>
                    <FieldLabel htmlFor="nome">Nome da Instituição</FieldLabel>
                    <Input
                      id="nome"
                      type="text"
                      placeholder="Ex.: Escola Secundária de Luanda"
                      value={data.nome}
                      onChange={(e) => setData('nome', e.target.value)}
                    />
                    {errors.nome && <FieldError>{errors.nome}</FieldError>}
                  </Field>

                  <Field>
                    <FieldLabel htmlFor="sigla">Sigla</FieldLabel>
                    <Input
                      id="sigla"
                      type="text"
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

                {/* Tipo & Email */}
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <Field>
                    <FieldLabel htmlFor="tipo">Tipo</FieldLabel>
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
                    <FieldLabel htmlFor="email">E-mail</FieldLabel>
                    <Input
                      id="email"
                      type="email"
                      placeholder="Ex.: director@escola.ao"
                      value={data.email}
                      onChange={(e) => setData('email', e.target.value)}
                    />
                    {errors.email && <FieldError>{errors.email}</FieldError>}
                  </Field>
                </div>

                {/* Telefone & Província */}
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <Field>
                    <FieldLabel htmlFor="telefone">Telefone</FieldLabel>
                    <div className="flex">
                      <span className="flex items-center justify-center px-3 py-1 text-xs border border-r-0 text-muted-foreground">
                        +244
                      </span>
                      <Input
                        id="telefone"
                        type="tel"
                        placeholder="Ex.: 923000000"
                        value={data.telefone}
                        onChange={(e) => setData('telefone', e.target.value)}
                      />
                    </div>
                    {errors.telefone && (
                      <FieldError>{errors.telefone}</FieldError>
                    )}
                  </Field>

                  <Field>
                    <FieldLabel htmlFor="provincia">Província</FieldLabel>
                    <Input
                      id="provincia"
                      type="text"
                      placeholder="Ex.: Luanda"
                      value={data.provincia}
                      onChange={(e) => setData('provincia', e.target.value)}
                    />
                    {errors.provincia && (
                      <FieldError>{errors.provincia}</FieldError>
                    )}
                  </Field>
                </div>

                {/* Endereço */}
                <Field>
                  <FieldLabel htmlFor="endereco">Endereço</FieldLabel>
                  <Input
                    id="endereco"
                    type="text"
                    placeholder="Ex.: Rua da Escola, nº 123"
                    value={data.endereco}
                    onChange={(e) => setData('endereco', e.target.value)}
                  />
                  {errors.endereco && (
                    <FieldError>{errors.endereco}</FieldError>
                  )}
                </Field>

                <Field>
                  <FieldLabel htmlFor="domain">Subdomínio</FieldLabel>
                  <ButtonGroup>
                    <Button variant="outline">http://</Button>
                    <Input
                      id="domain"
                      type="text"
                      placeholder="Ex.: escola-001.sge.ao"
                      value={data.domain}
                      onChange={(e) => setData('domain', e.target.value)}
                    />
                    <Button variant="outline">.sge.localhost</Button>
                  </ButtonGroup>

                  {errors.domain && <FieldError>{errors.domain}</FieldError>}
                </Field>

                {/* Status */}
                <Field>
                  <div className="flex items-center gap-3">
                    <Checkbox
                      id="status"
                      checked={data.status}
                      onCheckedChange={(checked) => setData('status', checked)}
                    />
                    <FieldLabel
                      htmlFor="status"
                      className="mb-0 cursor-pointer"
                    >
                      Ativo
                    </FieldLabel>
                  </div>
                  {errors.status && <FieldError>{errors.status}</FieldError>}
                </Field>

                {/* Submit Button */}
                <Field>
                  <Button
                    type="submit"
                    disabled={processing || !canSubmit}
                    className="w-full"
                  >
                    {processing ? (
                      <>
                        <Loader2 className="animate-spin" /> {submitLabel}...
                      </>
                    ) : (
                      <>{submitLabel}</>
                    )}
                  </Button>
                </Field>
              </FieldSet>
            </FieldGroup>
          </CardContent>
        </Card>
      </form>
    </div>
  );
}
