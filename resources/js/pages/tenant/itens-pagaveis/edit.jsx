import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import { ItensForm } from './components/itens-form';
import { update } from '@/actions/App/Http/Controllers/Tenant/ItemPagavelController';

export default function Edit({
  itemPagavel,
  cursosClasse = [],
  instituicaoTipo,
}) {
  const { put, data, setData, processing, errors } = useForm({
    nome: itemPagavel.nome ?? '',
    tipo: itemPagavel.tipo ?? '',
    subtipo: itemPagavel.subtipo ?? '',
    descricao: itemPagavel.descricao ?? '',
    valor: itemPagavel.valor ?? '',
    frequencia: itemPagavel.frequencia ?? 'mensal',
    curso_classe_id:
      itemPagavel.curso_classe_id != null
        ? String(itemPagavel.curso_classe_id)
        : '',
    ativo: itemPagavel.ativo ?? true,
    multa_dias_tolerancia: itemPagavel.multa_dias_tolerancia ?? '',
    multa_valor: itemPagavel.multa_valor ?? '',
  });

  return (
    <ItensForm
      title="Editar item pagável"
      submitLabel="Actualizar item"
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      cursosClasse={cursosClasse}
      instituicaoTipo={instituicaoTipo}
      submitFn={(e) => {
        e.preventDefault();
        put(update(itemPagavel.id).url);
      }}
    />
  );
}
