import { Head, useForm } from '@inertiajs/react';
import { update } from '@/actions/App/Http/Controllers/Tenant/UserController';
import { UserForm } from './components/user-form';

export default function Edit({ user, roles, currentUser }) {
  const form = useForm({
    id: user.id,
    nome: user.nome,
    email: user.email,
    telefone: user.telefone ?? '',
    password: '',
    roles: user.roles.map((role) => role.name),
  });

  return (
    <>
      <Head title="Editar usuário" />

      <UserForm
        {...form}
        roles={roles}
        currentUser={currentUser}
        title="Editar usuário"
        description="Altere as informações do usuário e clique em salvar alterações."
        submitLabel="Salvar alterações"
        processingLabel="Salvando alterações"
        submit={(event) => {
          event.preventDefault();
          form.put(update(user.id).url);
        }}
      />
    </>
  );
}
