import { Head, useForm } from '@inertiajs/react';
import { CursoForm } from './components/curso-form';
import { update } from '@/actions/App/Http/Controllers/Central/CursoController';

export default function Edit({ curso }) {
  const { put, data, setData, processing, errors } = useForm({
    nome: curso.nome,
    descricao: curso.descricao ?? '',
    duracao_anos: curso.duracao_anos,
    status: curso.status,
  });

  return (
    <>
      <Head title="Editar curso" />
      <CursoForm
        title="Editar curso"
        description="Atualize os campos abaixo para editar o curso do catálogo central."
        data={data}
        setData={setData}
        errors={errors}
        processing={processing}
        onSubmit={(event) => {
          event.preventDefault();
          put(update(curso.id).url);
        }}
      />
    </>
  );
}
