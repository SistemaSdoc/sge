import { useForm } from '@inertiajs/react';
import { TurnoForm } from './components/turno-form';

export default function Edit({ turno }) {
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
      submitFn={(e) => {
        e.preventDefault();
        put(`/turnos/${turno.id}`);
      }}
    />
  );
}
