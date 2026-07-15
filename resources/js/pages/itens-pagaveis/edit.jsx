import { useForm } from '@inertiajs/react';
import { ItensForm } from './components/itens-form';
import { update } from '@/actions/App/Http/Controllers/ItemPagavelController';

export default function Edit({ itemPagavel }) {
  const { put, data, setData, processing, errors } = useForm({
    nome: itemPagavel.nome,
    tipo: itemPagavel.tipo,
    valor_padrao: itemPagavel.valor_padrao,
  });

  return (
    <ItensForm
      title="Editar item pagável"
      submitLabel="Actualizar item"
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      submitFn={(e) => {
        e.preventDefault();
        put(update(itemPagavel.id).url);
      }}
    />
  );
}
