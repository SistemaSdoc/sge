import { Head, useForm } from '@inertiajs/react';
import { DisciplinaForm } from './components/disciplina-form';
import { update } from '@/actions/App/Http/Controllers/Central/DisciplinaController';

export default function Edit({ disciplina }) {
  const { put, data, setData, processing, errors } = useForm({
    nome: disciplina.nome,
    sigla: disciplina.sigla ?? '',
    componente: disciplina.componente ?? '',
    carga_horaria: disciplina.carga_horaria,
    status: disciplina.status,
  });

  return (
    <>
      <Head title="Editar disciplina" />
      <DisciplinaForm
        title="Editar disciplina"
        description="Actualize os dados da disciplina no catálogo central."
        data={data}
        setData={setData}
        errors={errors}
        processing={processing}
        onSubmit={(event) => {
          event.preventDefault();
          put(update(disciplina.id).url);
        }}
      />
    </>
  );
}
