import { Head, useForm } from '@inertiajs/react';
import AnoLectivoForm from './components/ano-lectivo-form';
import { store } from '@/actions/App/Http/Controllers/Central/AnoLectivoController';

export default function Create() {
  const { post, data, setData, processing, errors } = useForm({
    ano_inicio: new Date().getFullYear(),
  });

  return (
    <>
      <Head title="Adicionar ano lectivo" />
      <AnoLectivoForm
        title="Adicionar ano lectivo"
        description="Indique o ano de início. As datas e o estado serão calculados automaticamente."
        data={data}
        setData={setData}
        errors={errors}
        processing={processing}
        onSubmit={(event) => {
          event.preventDefault();
          post(store().url);
        }}
      />
    </>
  );
}
