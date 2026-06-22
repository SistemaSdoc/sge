import { useForm } from '@inertiajs/react';
import { InstituicaoForm } from './components/instituicao-form';
import { store } from '@/actions/App/Http/Controllers/InstituicaoController';

export default function Create() {
  const { post, data, setData, processing, errors } = useForm({
    nome: '',
    sigla: '',
    tipo: '',
    telefone: '',
    email: '',
    endereco: '',
    logo: null,
  });

  return (
    <InstituicaoForm
      title="Adicionar Instituição"
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      submitLabel="Adicionar"
      submitFn={(e) => {
        e.preventDefault();
        post(store().url, {
          forceFormData: true,
        });
      }}
    />
  );
}
