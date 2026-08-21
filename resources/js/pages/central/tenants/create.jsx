import { useForm } from '@inertiajs/react';
import { TenantForm } from './components/tenant-form';
import { store } from '@/actions/App/Http/Controllers/Central/TenantController';

export default function Create({ can = {} }) {
  const { post, data, setData, processing, errors } = useForm({
    tenant_id: '',
    domain: '',
    nome: '',
    sigla: '',
    tipo: 'colegio',
    email: '',
    telefone: '',
    provincia: '',
    endereco: '',
    status: true,
    user_nome: '',
    user_email: '',
  });

  return (
    <TenantForm
      title="Criar Nova Instituição"
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      can={can}
      submitFn={(e) => {
        e.preventDefault();
        post(store().url);
      }}
    />
  );
}
