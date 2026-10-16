import { Link } from '@inertiajs/react';
import { ArrowLeft, ArrowUpLeft } from 'lucide-react';
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
import { Textarea } from '@/components/ui/textarea';
import { index } from '@/actions/App/Http/Controllers/Tenant/ProfessorController';

export function ProfessorForm({
  title,
  submitLabel = 'Cadastrar',
  data,
  setData,
  errors,
  processing,
  submitFn,
}) {
  return (
    <div className="mx-auto w-full max-w-sm p-4 md:max-w-md md:p-6 lg:max-w-2xl">
      <form onSubmit={submitFn}>
        <Card>
          <CardHeader className="border-b">
            <CardTitle>{title}</CardTitle>
          </CardHeader>
          <CardContent>
            <FieldGroup>
              <FieldSet>
                <Field>
                  <FieldLabel htmlFor="nome">Nome</FieldLabel>
                  <Input
                    id="nome"
                    type="text"
                    placeholder="Ex.: João Silva"
                    value={data.nome}
                    onChange={(e) => setData('nome', e.target.value)}
                  />
                  {errors.nome && <FieldError>{errors.nome}</FieldError>}
                </Field>

                <Field>
                  <FieldLabel htmlFor="bi">Nº Bilhete</FieldLabel>
                  <Input
                    id="bi"
                    type="text"
                    disabled={processing}
                    value={data.bi}
                    placeholder="Ex.: 020419607LA096"
                    onChange={(e) => setData('bi', e.target.value)}
                  />
                  {errors?.bi && <FieldError>{errors.bi}</FieldError>}
                </Field>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <Field>
                    <FieldLabel htmlFor="telefone">Telefone</FieldLabel>
                    <div className="flex">
                      <span className="flex items-center justify-center border border-r-0 px-3 py-1 text-xs text-muted-foreground">
                        +244
                      </span>

                      <Input
                        id="telefone"
                        type="tel"
                        disabled={processing}
                        placeholder="Ex.: 950000000"
                        value={data.telefone}
                        onChange={(e) => setData('telefone', e.target.value)}
                      />
                    </div>
                    {errors?.telefone && (
                      <FieldError>{errors.telefone}</FieldError>
                    )}
                  </Field>

                  <Field>
                    <FieldLabel htmlFor="email">E-mail</FieldLabel>
                    <Input
                      id="email"
                      type="email"
                      disabled={processing}
                      placeholder="EX.: email@exemplo.com"
                      value={data.email}
                      onChange={(e) => setData('email', e.target.value)}
                    />
                    {errors?.email && <FieldError>{errors.email}</FieldError>}
                  </Field>
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <Field>
                    <FieldLabel htmlFor="especialidade">
                      Especialidade
                    </FieldLabel>
                    <Input
                      id="especialidade"
                      type="text"
                      disabled={processing}
                      placeholder="Ex.: Matemática"
                      value={data.especialidade}
                      onChange={(e) => setData('especialidade', e.target.value)}
                    />
                    {errors?.especialidade && (
                      <FieldError>{errors.especialidade}</FieldError>
                    )}
                  </Field>

                  <Field>
                    <FieldLabel htmlFor="nivel_academico">
                      Nível Académico
                    </FieldLabel>
                    <Input
                      id="nivel_academico"
                      type="text"
                      disabled={processing}
                      placeholder="Ex.: Licenciatura"
                      value={data.nivel_academico}
                      onChange={(e) =>
                        setData('nivel_academico', e.target.value)
                      }
                    />
                    {errors?.nivel_academico && (
                      <FieldError>{errors.nivel_academico}</FieldError>
                    )}
                  </Field>
                </div>

                <Field>
                  <Button
                    type="submit"
                    disabled={processing}
                    className="hover:cursor-pointer"
                  >
                    {processing ? 'Cadastrando...' : submitLabel}
                  </Button>

                  <Button asChild variant="outline">
                    <Link href={index().url}>
                      <ArrowUpLeft aria-hidden="true" />
                      Voltar à lista de professores
                    </Link>
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
