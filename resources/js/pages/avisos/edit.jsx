import { useForm } from '@inertiajs/react';
import { AvisoForm } from './components/aviso-form';
import { update } from '@/actions/App/Http/Controllers/Tenant/AvisoController';

export default function Edit({ aviso }) {
  const { put, data, setData, processing, errors } = useForm({
    titulo: aviso.titulo,
    descricao: aviso.descricao,
    ativo: aviso.ativo,
    tipo: aviso.tipo,
    destinatario: aviso.destinatario,
    data: aviso.data ? aviso.data.slice(0, 16) : '',
  });

  return (
    <AvisoForm
      title="Editar Aviso"
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      submitFn={(e) => {
        e.preventDefault();
        put(update(aviso.id).url);
      }}
    />
  );
}
