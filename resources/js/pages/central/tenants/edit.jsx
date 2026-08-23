import { useForm } from '@inertiajs/react';
import { TenantForm } from './components/tenant-form';
import { update } from '@/actions/App/Http/Controllers/Central/TenantController';

export default function Edit({ can = {}, tenant }) {
  const { put, data, setData, processing, errors } = useForm({
    nome: tenant.nome,
    sigla: tenant.sigla,
    domain: tenant.domain,
    tipo: tenant.tipo,
    user_nome: tenant.user_nome,
    user_email: tenant.user_email,
  });

  return (
    <TenantForm
      title="Editar Instituição"
      description="Edite os dados da instituição abaixo"
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      processingLabel="Salvando alterações"
      submitLabel="Salvar alterações"
      can={can}
      submitFn={(e) => {
        e.preventDefault();
        put(update(tenant.id).url);
      }}
    />
  );
}
