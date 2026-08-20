import { useForm } from '@inertiajs/react';
import { CursoForm } from './components/curso-form';
import { update } from '@/actions/App/Http/Controllers/Tenant/CursosController';

export default function Edit({ can = {}, curso }) {
  const { put, data, setData, processing, errors } = useForm({
    nome: curso.nome,
    duracao_anos: curso.duracao_anos,
    descricao: curso.descricao,
  });

  return (
    <CursoForm
      title="Editar Curso"
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      can={can}
      submitFn={(e) => {
        e.preventDefault();
        put(update(curso.id).url);
      }}
    />
  );
}
