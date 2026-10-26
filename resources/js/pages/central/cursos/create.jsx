import { Head, useForm } from '@inertiajs/react';
import { CursoForm } from './components/curso-form';
import { store } from '@/actions/App/Http/Controllers/Central/CursoController';

export default function Create() {
  const { post, data, setData, processing, errors } = useForm({
    nome: '',
    descricao: '',
    duracao_anos: 4,
    status: 1,
  });

  return (
    <>
      <Head title="Adicionar curso" />
      <CursoForm
        title="Adicionar curso"
        description="Preencha os campos abaixo para adicionar um novo curso ao catálogo central."
        data={data}
        setData={setData}
        errors={errors}
        processing={processing}
        onSubmit={(event) => {
          event.preventDefault();
          post(store().url);
        }}
      />
    </>
  );
}
