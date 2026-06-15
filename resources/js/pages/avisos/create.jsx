import { useForm } from '@inertiajs/react';
import { AvisoForm } from './components/aviso-form';

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
        post('/avisos');
      }}
    />
  );
}
