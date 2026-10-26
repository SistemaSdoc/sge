import { Head, useForm } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/Tenant/RoleController';
import { RoleForm } from './components/role-form';

export default function Create({ permissions, groupedPermissions }) {
  const form = useForm({ name: '', permissions: [] });

  return (
    <>
      <Head title="Nova função" />

      <RoleForm
        {...form}
        permissions={permissions}
        groupedPermissions={groupedPermissions}
        title="Nova função"
        description="Preencha os campos abaixo para criar uma nova função."
        submitLabel="Adicionar função"
        processingLabel="Adicionando função"
        submit={(event) => {
          event.preventDefault();
          form.post(store().url);
        }}
      />
    </>
  );
}
