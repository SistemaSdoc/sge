import { Link } from '@inertiajs/react';
import { ArrowUpLeft, Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { index } from '@/actions/App/Http/Controllers/Tenant/RoleController';
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

export function RoleForm({
  data,
  setData,
  errors,
  processing,
  submit,
  permissions = [],
  groupedPermissions = [],
  title,
  description,
  submitLabel = 'Salvar',
  processingLabel = 'Salvando',
}) {
  const [search, setSearch] = useState('');

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
            <CardTitle>{title}</CardTitle>
            <CardDescription>{description}</CardDescription>
          </CardHeader>

          <CardContent>
            <FieldGroup>
              <FieldSet className="">
                <Field>
                  <FieldLabel htmlFor="name">Nome da função</FieldLabel>

                  <Input
                    id="name"
                    type="text"
                    placeholder="Ex.: Coordenador de curso"
                    disabled={processing}
                    value={data.name}
                    onChange={(event) => setData('name', event.target.value)}
                  />

                  {errors.name && <FieldError>{errors.name}</FieldError>}
                </Field>

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
                                        disabled={processing}
                                        onCheckedChange={(checked) =>
                                          togglePermission(permission, checked)
                                        }
                                      />
                                      <span className="break-all">
                                        {permissionLabel}
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

                  {errors.permissions && (
                    <FieldError>{errors.permissions}</FieldError>
                  )}
                </Field>

                <Field className="mt-4">
                  <Button type="submit" disabled={processing}>
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
                      Voltar a lista de funções
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
