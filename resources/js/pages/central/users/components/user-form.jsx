import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/spinner';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
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
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

export function UserForm({
  title,
  description,
  submitLabel = 'Adicionar',
  processingLabel,
  data,
  setData,
  errors,
  processing,
  submitFn,
  roles = [],
}) {
  return (
    <div className="mx-auto w-full max-w-2xl px-6 py-6">
      <form onSubmit={submitFn}>
        <Card>
          <CardHeader className="border-b">
            <CardTitle>{title}</CardTitle>
            <CardDescription>{description}</CardDescription>
          </CardHeader>

          <CardContent>
            <FieldGroup>
              <FieldSet>
                <Field>
                  <FieldLabel htmlFor="nome">Nome</FieldLabel>
                  <Input
                    id="nome"
                    type="text"
                    placeholder="Nome completo"
                    value={data.nome}
                    onChange={(e) => setData('nome', e.target.value)}
                  />
                  {errors.nome && <FieldError>{errors.nome}</FieldError>}
                </Field>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <Field>
                    <FieldLabel htmlFor="email">Email</FieldLabel>
                    <Input
                      id="email"
                      type="email"
                      placeholder="email@exemplo.com"
                      value={data.email}
                      onChange={(e) => setData('email', e.target.value)}
                    />
                    {errors.email && <FieldError>{errors.email}</FieldError>}
                  </Field>

                  <Field>
                    <FieldLabel htmlFor="telefone">Telefone</FieldLabel>
                    <Input
                      id="telefone"
                      type="text"
                      placeholder="+244 9xx xxx xxx"
                      value={data.telefone}
                      onChange={(e) => setData('telefone', e.target.value)}
                    />
                    {errors.telefone && (
                      <FieldError>{errors.telefone}</FieldError>
                    )}
                  </Field>
                </div>

                <Field>
                  <FieldLabel htmlFor="password">
                    Password{' '}
                    {data.id && (
                      <span className="font-normal text-muted-foreground"></span>
                    )}
                  </FieldLabel>
                  <Input
                    id="password"
                    type="password"
                    placeholder="••••••••"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                  />
                  {errors.password && (
                    <FieldError>{errors.password}</FieldError>
                  )}
                </Field>

                {roles.length > 0 && (
                  <Field>
                    <FieldLabel htmlFor="role">Perfil</FieldLabel>
                    <Select
                      value={String(data.roles?.[0] ?? '')}
                      onValueChange={(val) => setData('roles', [Number(val)])}
                    >
                      <SelectTrigger className="w-full">
                        <SelectValue placeholder="Seleccionar perfil" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectGroup>
                          <SelectLabel>Perfis</SelectLabel>
                          {roles.map((role) => (
                            <SelectItem key={role.id} value={String(role.id)}>
                              {role.name}
                            </SelectItem>
                          ))}
                        </SelectGroup>
                      </SelectContent>
                    </Select>
                    {errors.roles && <FieldError>{errors.roles}</FieldError>}
                  </Field>
                )}

                <Button type="submit" className="w-full" disabled={processing}>
                  {processing ? (
                    <>
                      <Spinner className="size-4" /> {processingLabel}{' '}
                    </>
                  ) : (
                    submitLabel
                  )}
                </Button>
              </FieldSet>
            </FieldGroup>
          </CardContent>
        </Card>
      </form>
    </div>
  );
}
