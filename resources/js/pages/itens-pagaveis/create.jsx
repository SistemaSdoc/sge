import { useForm } from '@inertiajs/react';
import { ItensForm } from './components/itens-form';
import { store } from '@/actions/App/Http/Controllers/Tenant/ItemPagavelController';

export default function Create({ cursosClasse }) {
  const { post, data, setData, processing, errors } = useForm({
    nome: '',
    tipo: '',
    descricao: '',
    valor: '',
    frequencia: '',
    curso_classe_id: '',
    ativo: true,
  });

  return (
    <ItensForm
      title="Novo Emolumento Escolar"
      submitLabel="Criar emolumento"
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      cursosClasse={cursosClasse}
      submitFn={(e) => {
        e.preventDefault();
        post(store().url);
      }}
    />
  );
}
