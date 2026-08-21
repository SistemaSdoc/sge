import { useForm } from '@inertiajs/react';
import { InstituicaoForm } from './components/instituicao-form';
import { update } from '@/actions/App/Http/Controllers/Tenant/InstituicaoController';

export default function Edit({ can = {}, instituicao, logoUrl }) {
  const { put, data, setData, processing, errors } = useForm({
    nome: instituicao.nome,
    sigla: instituicao.sigla,
    tipo: instituicao.tipo,
    telefone: instituicao.telefone,
    email: instituicao.email,
    endereco: instituicao.endereco,
    logo: null,
  });

  return (
    <InstituicaoForm
      title="Editar Instituição"
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      can={can}
      submitLabel="Actualizar"
      logoUrl={logoUrl}
      submitFn={(e) => {
        e.preventDefault();
        put(update(instituicao.id).url, {
          forceFormData: true,
        });
      }}
    />
  );
}
