import { Link } from '@inertiajs/react';
import { ArrowUpLeft, Info } from 'lucide-react';
import { useState } from 'react';
import { index } from '@/actions/App/Http/Controllers/Tenant/UserController';
import MultipleSelect from '@/components/multiple-select';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardAction,
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
import { Spinner } from '@/components/spinner';
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from '@/components/ui/tooltip';

export function UserForm({
  data,
  setData,
  errors,
  processing,
  submit,
  roles,
  title,
  description,
  submitLabel = 'Salvar',
  processingLabel = 'Salvando as alterações',
  currentUser = {},
  rolesDisabled,
}) {
  const [infoOpen, setInfoOpen] = useState(false);

  const isLockedSelfRoleAssignment = Boolean(
    currentUser?.isSubdirector && currentUser?.id === data?.id,
  );
  const isProtectedDirectorUser = Boolean(
    data?.isDirector &&
    !currentUser?.isSuperAdmin &&
    currentUser?.id !== data?.id,
  );

  const selectedRoles = (data.roles ?? []).map((roleName) => ({
    value: roleName,
    label: roleName,
  }));

  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md lg:max-w-2xl">
      <form onSubmit={submit}>
        <Card className="overflow-visible">
          <CardHeader className="border-b">
            <CardTitle>{title}</CardTitle>
            <CardDescription>{description}</CardDescription>
            <CardAction>
              <Tooltip open={infoOpen} onOpenChange={setInfoOpen}>
                <TooltipTrigger asChild className="bg-background">
                  <Button
                    size={'icon-sm'}
                    variant={'ghost'}
                    type="button"
                    className="hover:cursor-pointer"
                    onMouseEnter={() => setInfoOpen(true)}
                    onMouseLeave={() => setInfoOpen(false)}
                    onClick={() => setInfoOpen((current) => !current)}
                  >
                    <Info />
                  </Button>
                </TooltipTrigger>

                <TooltipContent
                  side="bottom"
                  className="flex max-w-xs flex-col border bg-background"
                >
                  <p className="font-medium text-foreground">Nota importante</p>
                  <p className="mt-1 text-xs text-muted-foreground">
                    Quando criar um usuário, as credenciais de acesso serão
                    enviadas por e-mail.
                  </p>
                </TooltipContent>
              </Tooltip>
            </CardAction>
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
                    disabled={processing}
                    value={data.nome}
                    onChange={(event) => setData('nome', event.target.value)}
                  />

                  {errors.nome && <FieldError>{errors.nome}</FieldError>}
                </Field>

                <Field>
                  <FieldLabel htmlFor="email">E-mail</FieldLabel>

                  <Input
                    id="email"
                    type="email"
                    placeholder="Ex.: joao@exemplo.com"
                    disabled={processing}
                    value={data.email}
                    onChange={(event) => setData('email', event.target.value)}
                  />

                  {errors.email && <FieldError>{errors.email}</FieldError>}
                </Field>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <Field>
                    <FieldLabel htmlFor="telefone">Telefone</FieldLabel>

                    <Input
                      id="telefone"
                      type="tel"
                      placeholder="Ex.: 950000000"
                      disabled={processing}
                      value={data.telefone}
                      onChange={(event) =>
                        setData('telefone', event.target.value)
                      }
                    />

                    {errors.telefone && (
                      <FieldError>{errors.telefone}</FieldError>
                    )}
                  </Field>

                  <Field>
                    <FieldLabel htmlFor="password">
                      {data.id
                        ? 'Nova palavra-passe (opcional)'
                        : 'Palavra-passe'}
                    </FieldLabel>

                    <Input
                      id="password"
                      type="password"
                      disabled={processing}
                      value={data.password}
                      onChange={(event) =>
                        setData('password', event.target.value)
                      }
                    />

                    {errors.password && (
                      <FieldError>{errors.password}</FieldError>
                    )}
                  </Field>
                </div>

                <Field>
                  <FieldLabel>Papéis</FieldLabel>

                  <MultipleSelect
                    placeholder="Selecione as funções"
                    items={roles.map((role) => ({
                      value: role.name,
                      label: role.name,
                    }))}
                    value={selectedRoles}
                    disabled={
                      isLockedSelfRoleAssignment ||
                      isProtectedDirectorUser ||
                      rolesDisabled ||
                      processing
                    }
                    onChange={(options) =>
                      setData(
                        'roles',
                        options.map((option) => String(option.value)),
                      )
                    }
                  />

                  {(isLockedSelfRoleAssignment || isProtectedDirectorUser) && (
                    <p className="mt-2 text-xs text-muted-foreground">
                      {isProtectedDirectorUser
                        ? 'Este utilizador é o Director e não pode ser alterado por um subdiretor.'
                        : 'Como subdiretor, não pode alterar as suas próprias funções.'}
                    </p>
                  )}

                  {errors.roles && <FieldError>{errors.roles}</FieldError>}
                </Field>

                <Field>
                  <Button
                    type="submit"
                    disabled={processing || isProtectedDirectorUser}
                  >
                    {processing ? (
                      <>
                        <Spinner />
                        {processingLabel}
                      </>
                    ) : (
                      submitLabel
                    )}
                  </Button>

                  <Button asChild variant="outline" disabled={processing}>
                    <Link href={index().url}>
                      <ArrowUpLeft />
                      Voltar a lista de usuários
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
