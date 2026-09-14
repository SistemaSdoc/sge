import { Head, useForm } from '@inertiajs/react';
import { update } from '@/actions/App/Http/Controllers/Tenant/UserPermissionController';
import { UserPermissionsForm } from './components/user-permission-form';

export default function Permissions({
  user,
  allPermissions,
  groupedPermissions,
  currentUser,
}) {
  const inheritedPermissions = new Set(user?.inheritedPermissions ?? []);

  const form = useForm({
    permissions: Array.from(
      new Set([
        ...(user.directPermissions ?? []),
        ...(user.inheritedPermissions ?? []),
      ]),
    ),
  });

  const submit = (event) => {
    event.preventDefault();

    form.transform((data) => ({
      ...data,
      permissions: data.permissions.filter(
        (permission) => !inheritedPermissions.has(permission),
      ),
    }));

    form.put(update(user.id).url, {
      preserveScroll: true,
    });
  };

  return (
    <>
      <Head title="Gerir permissões" />

      <UserPermissionsForm
        {...form}
        currentUser={currentUser}
        user={{
          ...user,
          inheritedPermissions: user.inheritedPermissions ?? [],
          directPermissions: user.directPermissions ?? [],
        }}
        permissions={allPermissions}
        groupedPermissions={groupedPermissions}
        submitLabel="Salvar permissões"
        processingLabel="Salvando permissões"
        submit={submit}
      />
    </>
  );
}
