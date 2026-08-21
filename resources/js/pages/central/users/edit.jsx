import { useForm } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import { UserForm } from './components/user-form';
import { update } from '@/actions/App/Http/Controllers/Central/UserController';

export default function Edit({ user, roles }) {
  const { put, data, setData, processing, errors } = useForm({
    nome: user.nome ?? '',
    email: user.email ?? '',
    bi: user.bi ?? '',
    telefone: user.telefone ?? '',
    password: '',
    roles: user.roles?.map((r) => r.id) ?? [],
  });

  return (
    <>
      <Head title="Editar Usuário" />

      <UserForm
        title="Editar Usuário"
        description="Altere os dados do usuário e salve alterações"
        submitLabel="Actualizar"
        processingLabel="Actualizando"
        roles={roles}
        data={{ ...data, id: user.id }}
        setData={setData}
        errors={errors}
        processing={processing}
        submitFn={(e) => {
          e.preventDefault();
          put(update(user.id).url);
        }}
      />
    </>
  );
}