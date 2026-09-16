import { Link } from '@inertiajs/react';
import { ArrowUpLeft, Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { index } from '@/actions/App/Http/Controllers/Tenant/UserController';
import { Button } from '@/components/ui/button';
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
import { Checkbox } from '@/components/ui/checkbox';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Spinner } from '@/components/spinner';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';

export function UserPermissionsForm({
  user,
  data,
  setData,
  errors,
  processing,
  submit,
  permissions = [],
  groupedPermissions = [],
  submitLabel = 'Salvar',
  processingLabel = 'Salvando as alterações',
  currentUser = {},
}) {
  const getInitials = useInitials();

  const [search, setSearch] = useState('');

  const isLockedSelfPermissionAssignment = Boolean(
    currentUser?.isSubdirector && currentUser?.id === user?.id,
  );
  const isProtectedDirectorUser = Boolean(
    user?.isDirector &&
    !currentUser?.isSuperAdmin &&
    currentUser?.id !== user?.id,
  );

  const inheritedPermissions = new Set(user?.inheritedPermissions ?? []);

  const permissionGroups = groupedPermissions.length
    ? groupedPermissions
    : [{ label: 'Permissões', permissions }];

  const filteredPermissionGroups = useMemo(() => {
    const query = search.trim().toLowerCase();

    if (!query) {
      return permissionGroups;
    }

    return permissionGroups
      .map((group) => ({
        ...group,
        permissions: (group.permissions ?? []).filter((permission) => {
          const value = permission.value ?? permission;
          const label = permission.label ?? value;

          return (
            String(value).toLowerCase().includes(query) ||
            String(label).toLowerCase().includes(query)
          );
        }),
      }))
      .filter(
        (group) =>
          (group.permissions ?? []).length > 0 ||
          group.label.toLowerCase().includes(query),
      );
  }, [permissionGroups, search]);

  const togglePermission = (permission, checked) => {
    const value = permission.value ?? permission;

    setData(
      'permissions',
      checked
        ? [...data.permissions, value]
        : data.permissions.filter((name) => name !== value),
    );
  };

  return (
    <div className="mx-auto w-full max-w-sm px-4 py-2 md:max-w-md md:px-6 lg:max-w-3xl lg:px-8">
      <form onSubmit={submit}>
        <Card className="overflow-hidden">
          <CardHeader className="border-b">
            <CardTitle>Gerir permissões</CardTitle>
            <CardDescription>
              {user?.nome
                ? `Permissões individuais de ${user.nome}`
                : 'Permissões individuais do usuário'}
            </CardDescription>
          </CardHeader>

          <CardContent>
            <FieldGroup>
              <FieldSet>
                {user && (
                  <Field>
                    <div className="flex items-center gap-3 border bg-muted/40 p-3">
                      <Avatar>
                        <AvatarImage src={user.avatar} alt={user.nome} />
                        <AvatarFallback>
                          {getInitials(user.nome)}
                        </AvatarFallback>
                      </Avatar>

                      <div className="flex flex-col">
                        <span className="text-sm">{user.nome}</span>
                        <span className="text-xs text-muted-foreground">
                          Funções: {user.roles.join(', ')}
                        </span>
                      </div>
                    </div>
                  </Field>
                )}

                <Field>
                  <FieldLabel>Permissões</FieldLabel>

                  <div className="space-y-3">
                    <div className="relative">
                      <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                      <Input
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Pesquisar permissão..."
                        className="pl-9"
                      />
                    </div>

                    <ScrollArea className="h-72 p-4">
                      {filteredPermissionGroups.length > 0 ? (
                        <div className="space-y-5">
                          {filteredPermissionGroups.map((group) => (
                            <div key={group.label} className="space-y-3">
                              <h3 className="text-xs! font-semibold tracking-wide uppercase">
                                {group.label}
                              </h3>

                              <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                {(group.permissions ?? []).map((permission) => {
                                  const permissionValue =
                                    permission.value ?? permission;
                                  const permissionLabel =
                                    permission.label ?? permissionValue;

                                  return (
                                    <label
                                      key={permissionValue}
                                      className="flex items-center gap-2"
                                    >
                                      <Checkbox
                                        checked={data.permissions.includes(
                                          permissionValue,
                                        )}
                                        disabled={
                                          processing ||
                                          isLockedSelfPermissionAssignment ||
                                          isProtectedDirectorUser
                                        }
                                        onCheckedChange={(checked) =>
                                          togglePermission(permission, checked)
                                        }
                                      />
                                      <span className="break-all">
                                        {permissionLabel}
                                        {inheritedPermissions.has(
                                          permissionValue,
                                        ) && (
                                          <span className="ml-1 text-[10px] text-muted-foreground">
                                            (herdada)
                                          </span>
                                        )}
                                      </span>
                                    </label>
                                  );
                                })}
                              </div>
                            </div>
                          ))}
                        </div>
                      ) : (
                        <p className="py-6 text-center text-sm text-muted-foreground">
                          Nenhuma permissão encontrada.
                        </p>
                      )}
                    </ScrollArea>
                  </div>

                  {(isLockedSelfPermissionAssignment ||
                    isProtectedDirectorUser) && (
                    <p className="mt-2 text-xs text-muted-foreground">
                      {isProtectedDirectorUser
                        ? 'Este utilizador é o Director e não pode ter permissões alteradas por um subdiretor.'
                        : 'Como subdiretor, não pode alterar as suas próprias permissões.'}
                    </p>
                  )}

                  {errors.permissions && (
                    <FieldError>{errors.permissions}</FieldError>
                  )}
                </Field>

                <Field className="mt-4">
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
