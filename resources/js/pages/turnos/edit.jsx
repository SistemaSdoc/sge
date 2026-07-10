import { useForm } from '@inertiajs/react';
import { TurnoForm } from './components/turno-form';
import { update } from '@/actions/App/Http/Controllers/TurnoController';

export default function Edit({ can = {}, turno }) {
  const { put, data, setData, processing, errors } = useForm({
    nome: turno.nome,
  });

  return (
    <TurnoForm
      title="Editar Turno"
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      can={can}
      submitFn={(e) => {
        e.preventDefault();
        put(update(turno.id).url);
      }}
    />
  );
}
