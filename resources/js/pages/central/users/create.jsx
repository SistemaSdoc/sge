import { useForm } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import { UserForm } from './components/user-form';
import { store } from '@/actions/App/Http/Controllers/Central/UserController';

export default function Create({ roles }) {
  const { post, data, setData, processing, errors } = useForm({
    nome: '',
    email: '',
    bi: '',
    telefone: '',
    password: '',
    roles: [],
  });

  return (
    <>
      <Head title="Adicionar Usuário" />
      <UserForm
        title="Adicionar usuário"
        description="Preencha os dados abaixo para adicionar um novo usuário"
        roles={roles}
        submitLabel="Adicionar"
        processingLabel="Adicionando"
        data={data}
        setData={setData}
        errors={errors}
        processing={processing}
        submitFn={(e) => {
          e.preventDefault();
          post(store().url);
        }}
      />
    </>
  );
}