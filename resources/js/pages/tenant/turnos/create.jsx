import { useForm } from '@inertiajs/react';
import { TurnoForm } from './components/turno-form';
import { store } from '@/actions/App/Http/Controllers/Tenant/TurnoController';

export default function Create({ can = {} }) {
  const { post, data, setData, processing, errors } = useForm({
    nome: '',
  });

  return (
    <TurnoForm
      title="Adicionar Turno"
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
