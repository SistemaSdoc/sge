import { Head, useForm } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/Tenant/UserController';
import { UserForm } from './components/user-form';

export default function Create({ roles, currentUser }) {
  const form = useForm({
    nome: '',
    email: '',
    telefone: '',
    password: '',
    roles: [],
  });

  return (
    <>
      <Head title="Novo usuário" />

      <UserForm
        {...form}
        roles={roles}
        currentUser={currentUser}
        title="Novo usuário"
        description="Preencha os campos abaixo para criar um novo usuário."
        submitLabel="Adicionar usuário"
        processingLabel="Adicionando usuário"
        submit={(event) => {
          event.preventDefault();
          form.post(store().url);
        }}
      />
    </>
  );
}
