import { useForm } from "@inertiajs/react";
import { TurnoForm } from "./components/turno-form";

interface Turno {
  id: number;
  nome: string;
}


export default function Edit({ turno }: { turno: Turno }) {
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