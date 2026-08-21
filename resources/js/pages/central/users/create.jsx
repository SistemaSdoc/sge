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
      <Head title="Adicionar Utilizador" />
      <UserForm
        title="Adicionar Utilizador"
        roles={roles}
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