import { useForm } from '@inertiajs/react';
import { TenantForm } from './components/tenant-form';
import { store } from '@/actions/App/Http/Controllers/Central/TenantController';

export default function Create({ can = {} }) {
  const { post, data, setData, processing, errors } = useForm({
    nome: '',
    sigla: '',
    domain: '',
    tipo: '',
    user_nome: '',
    user_email: '',
  });

  return (
    <TenantForm
      title="Adicionar nova instituição"
      description="Preencha os campos abaixo para cadastrar uma nova instituição."
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      processingLabel="Adicionando Instituição"
      submitLabel="Adicionar Instituição"
      can={can}
      submitFn={(e) => {
        e.preventDefault();
        post(store().url);
      }}
    />
  );
}
