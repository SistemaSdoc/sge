import { useForm } from '@inertiajs/react';
import { ClasseForm } from './components/classe-form';
import { store } from '@/actions/App/Http/Controllers/Tenant/ClasseController';

export default function Create({ can = {} }) {
  const { post, data, setData, processing, errors } = useForm({
    nome: '',
    ordem: '',
  });

  return (
    <ClasseForm
      title="Adicionar Classe"
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
