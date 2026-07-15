import { useForm } from '@inertiajs/react';
import { ItensForm } from './components/itens-form';
import { store } from '@/actions/App/Http/Controllers/ItemPagavelController';

export default function Create() {
  const { post, data, setData, processing, errors } = useForm({
    nome: '',
    tipo: 'mensalidade',
    valor_padrao: '',
  });

  return (
    <ItensForm
      title="Novo item pagável"
      submitLabel="Criar item"
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
