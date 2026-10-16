import { Head, useForm } from '@inertiajs/react';
import { DisciplinaForm } from './components/disciplina-form';
import { store } from '@/actions/App/Http/Controllers/Central/DisciplinaController';

export default function Create() {
  const { post, data, setData, processing, errors } = useForm({
    nome: '',
    sigla: '',
    componente: '',
    carga_horaria: 60,
    status: 1,
  });

  return (
    <>
      <Head title="Adicionar disciplina" />
      <DisciplinaForm
        title="Adicionar disciplina"
        description="Preencha os campos abaixo para adicionar uma nova disciplina ao catálogo central."
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
