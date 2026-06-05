import { useForm } from '@inertiajs/react';
import { InstituicaoForm } from './components/instituicao-form';

export default function Edit({ instituicao }) {
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
      submitLabel="Actualizar"
      logoUrl={instituicao.logo ? `/storage/${instituicao.logo}` : null}
      submitFn={(e) => {
        e.preventDefault();
        put(`/instituicoes/${instituicao.id}`, {
          forceFormData: true,
        });
      }}
    />
  );
}
