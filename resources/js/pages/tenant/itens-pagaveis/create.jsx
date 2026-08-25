import { useForm } from '@inertiajs/react';
import { ItensForm } from './components/itens-form';
import { store } from '@/actions/App/Http/Controllers/Tenant/ItemPagavelController';

export default function Create({ cursosClasse, instituicaoTipo }) {
  const isColegio = instituicaoTipo === 'colegio';
  const { post, data, setData, processing, errors } = useForm({
    nome: '',
    tipo: isColegio ? 'financeiro' : 'documento', // ← inicializa conforme o tipo
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
      instituicaoTipo={instituicaoTipo}
      submitFn={(e) => {
        e.preventDefault();
        post(store().url);
      }}
    />
  );
}
