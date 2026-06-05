import { useForm } from '@inertiajs/react';
import { ClasseForm } from './components/classe-form';

export default function Create() {
  const { post, data, setData, processing, errors } = useForm({
    nome: '',
    ordem: ''
  });

  return (
    <ClasseForm
      title="Adicionar Classe"
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      submitFn={(e) => {
        e.preventDefault();
        post('/classes');
      }}
    />
  );
}
