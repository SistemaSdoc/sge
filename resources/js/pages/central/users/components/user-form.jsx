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
import { MultiSelect } from '@/components/ui/multi-select'; // ajusta ao teu componente

export function UserForm({
  title,
  submitLabel = 'Guardar',
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

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <Field>
                    <FieldLabel htmlFor="bi">Nº BI</FieldLabel>
                    <Input
                      id="bi"
                      type="text"
                      placeholder="000000000LA000"
                      value={data.bi}
                      onChange={(e) => setData('bi', e.target.value)}
                    />
                    {errors.bi && <FieldError>{errors.bi}</FieldError>}
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
                    {errors.telefone && <FieldError>{errors.telefone}</FieldError>}
                  </Field>
                </div>

                <Field>
                  <FieldLabel htmlFor="password">
                    Password{' '}
                    {data.id && (
                      <span className="font-normal text-muted-foreground">
                        (deixa em branco para manter)
                      </span>
                    )}
                  </FieldLabel>
                  <Input
                    id="password"
                    type="password"
                    placeholder="••••••••"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                  />
                  {errors.password && <FieldError>{errors.password}</FieldError>}
                </Field>

                {roles.length > 0 && (
                  <Field>
                    <FieldLabel>Perfis</FieldLabel>
                    <div className="flex flex-wrap gap-2">
                      {roles.map((role) => {
                        const selected = (data.roles ?? []).includes(role.id);
                        return (
                          <button
                            key={role.id}
                            type="button"
                            onClick={() => {
                              const current = data.roles ?? [];
                              setData(
                                'roles',
                                selected
                                  ? current.filter((id) => id !== role.id)
                                  : [...current, role.id],
                              );
                            }}
                            className={[
                              'rounded-full border px-3 py-1 text-sm transition-colors',
                              selected
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-input bg-background text-muted-foreground hover:bg-muted',
                            ].join(' ')}
                          >
                            {role.name}
                          </button>
                        );
                      })}
                    </div>
                    {errors.roles && <FieldError>{errors.roles}</FieldError>}
                  </Field>
                )}

                <Button type="submit" className="w-full" disabled={processing}>
                  {processing ? 'A guardar...' : submitLabel}
                </Button>
              </FieldSet>
            </FieldGroup>
          </CardContent>
        </Card>
      </form>
    </div>
  );
}