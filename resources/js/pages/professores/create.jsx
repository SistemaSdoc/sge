import { useForm } from '@inertiajs/react';
import { ProfessorForm } from './components/professor-form';
import { store } from '@/actions/App/Http/Controllers/ProfessorController';

export default function Create() {
  const { post, data, setData, processing, errors } = useForm({
    nome: '',
    bi: '',
    telefone: '',
    email: '',
  });

  return (
    <ProfessorForm
      title="Adicionar Professor"
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      submitFn={(e) => {
        e.preventDefault();
        post(store().url);
      }}
    />
  );
}
