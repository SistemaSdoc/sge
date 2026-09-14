import { Head, useForm } from '@inertiajs/react';
import { update } from '@/actions/App/Http/Controllers/Tenant/RoleController';
import { RoleForm } from './components/role-form';

export default function Edit({ role, permissions, groupedPermissions }) {
  const form = useForm({
    name: role.name,
    permissions: role.permissions.map((permission) => permission.name),
  });

  return (
    <>
      <Head title="Editar função" />

      <RoleForm
        {...form}
        permissions={permissions}
        groupedPermissions={groupedPermissions}
        title="Editar função"
        description="Altere as informações da função e clique em salvar alterações."
        submitLabel="Salvar alterações"
        processingLabel="Salvando alterações"
        submit={(event) => {
          event.preventDefault();
          form.put(update(role.id).url);
        }}
      />
    </>
  );
}
