import { useForm } from '@inertiajs/react';
import { AvisoForm } from './components/aviso-form';
import { store } from '@/actions/App/Http/Controllers/Tenant/AvisoController';

export default function Create() {
  const { post, data, setData, processing, errors } = useForm({
    titulo: '',
    descricao: '',
    tipo: '',
    data: '',
    ativo: true,
    destinatario: '',
  });

  return (
    <AvisoForm
      title="Adicionar Aviso"
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
