import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import { ItensForm } from './components/itens-form';
import { update } from '@/actions/App/Http/Controllers/ItemPagavelController';

export default function Edit({ itemPagavel, cursosClasse = [] }) {
  // Inicializa com valores vazios
  const { put, data, setData, processing, errors } = useForm({
    nome: '',
    tipo: '',
    descricao: '',
    valor: '',
    frequencia: 'mensal',
    curso_classe_id: '',
    ativo: true,
  });

  //  ESSENCIAL: sincroniza quando itemPagavel for carregado
  useEffect(() => {
    if (itemPagavel) {
      setData({
        nome: itemPagavel.nome ?? '',
        tipo: itemPagavel.tipo ?? '',
        descricao: itemPagavel.descricao ?? '',
        valor: itemPagavel.valor ?? '',
        frequencia: itemPagavel.frequencia ?? 'mensal',
        //  CONVERTE PARA STRING – O SELECT SÓ FUNCIONA COM STRINGS
        curso_classe_id: itemPagavel.curso_classe_id != null
          ? String(itemPagavel.curso_classe_id)
          : '',
        ativo: itemPagavel.ativo ?? true,
      });
    }
  }, [itemPagavel]);

  //  Logs para diagnóstico (remove depois)
  console.log('[Edit] itemPagavel:', itemPagavel);
  console.log('[Edit] cursosClasse IDs:', cursosClasse.map(c => String(c.id)));
  console.log('[Edit] data.curso_classe_id (tipo):', typeof data.curso_classe_id, data.curso_classe_id);

  return (
    <ItensForm
      title="Editar item pagável"
      submitLabel="Actualizar item"
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      cursosClasse={cursosClasse}
      submitFn={(e) => {
        e.preventDefault();
        console.log('[Edit] submit — data a enviar:', data);
        put(update(itemPagavel.id).url, {
          onSuccess: (page) => console.log('[Edit] onSuccess', page),
          onError: (errs) => console.log('[Edit] onError', errs),
          onFinish: () => console.log('[Edit] onFinish, processing=false'),
        });
      }}
    />
  );
}