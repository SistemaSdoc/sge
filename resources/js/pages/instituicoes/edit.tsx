import { useForm } from "@inertiajs/react";
import { InstituicaoForm } from "./components/instituicao-form";

interface Instituicao {
  id: number;
  nome: string;
  sigla: string;
  tipo: string;
  telefone: string;
  email: string;
  endereco: string;
  logo: null | File;
}


export default function Edit({ instituicao }: { instituicao: Instituicao }) {
  const { put, data, setData, processing, errors } = useForm({
    nome: instituicao.nome,
    sigla: instituicao.sigla,
    tipo: instituicao.tipo,
    telefone: instituicao.telefone,
    email: instituicao.email,
    endereco: instituicao.endereco,
    logo: null as null | File,
  });

  return (
    <InstituicaoForm
      title="Editar Instituição"
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      submitLabel="Atualizar"
      logoUrl={instituicao.logo ? `/storage/${instituicao.logo}` : null}
      submitFn={(e) => {
        e.preventDefault();
        put(`/instituicoes/${instituicao.id}`, {
          forceFormData: true,
        })
      }}
    />
  );
}