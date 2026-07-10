import { useForm } from '@inertiajs/react';
import { CursoForm } from './components/curso-form';
import { store } from '@/actions/App/Http/Controllers/CursosController';

export default function Create({ can = {} }) {
  const { post, data, setData, processing, errors } = useForm({
    nome: '',
    duracao_anos: '',
    descricao: '',
  });

  return (
    <CursoForm
      title="Adicionar Curso"
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      can={can}
      submitFn={(e) => {
        e.preventDefault();
        post(store().url);
      }}
    />
  );
}
