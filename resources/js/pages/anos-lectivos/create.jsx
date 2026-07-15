import { useForm } from '@inertiajs/react';
import { AnoLectivoForm } from './components/ano-lectivo-form';
import { store } from '@/actions/App/Http/Controllers/AnoLectivoController';

export default function Create() {
  const { post, data, setData, processing, errors } = useForm({
    data_inicio: '',
    data_fim: '',
  });

  return (
    <AnoLectivoForm
      title="Adicionar Ano Lectivo"
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
