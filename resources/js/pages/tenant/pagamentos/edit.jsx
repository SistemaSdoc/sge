import { useForm } from '@inertiajs/react';
import { PagamentosForm } from './components/pagamentos-form';
import { update } from '@/actions/App/Http/Controllers/Tenant/AvisoController';

export default function Edit({ pagamento, alunos = [], itensPagaveis = [] }) {
  const { put, data, setData, processing, errors } = useForm({
    aluno_id: pagamento?.aluno_id ?? '',
    estudante: pagamento?.estudante ?? pagamento?.aluno ?? '',
    referencia: pagamento?.referencia ?? pagamento?.mes ?? '',
    valor: pagamento?.valor ?? '',
    tipo: pagamento?.tipo ?? 'propina',
    metodo: pagamento?.metodo ?? 'multicaixa',
    estado: pagamento?.estado ?? 'pendente',
    descricao: pagamento?.descricao ?? '',
    data: pagamento?.data ? pagamento.data.slice(0, 16) : '',
    observacoes: pagamento?.observacoes ?? '',
    itens: pagamento?.itens ?? [],
    valor_total: pagamento?.valor_total ?? 0,
  });

  return (
    <PagamentosForm
      title="Editar pagamento"
      submitLabel="Actualizar pagamento"
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      alunos={alunos}
      itensPagaveis={itensPagaveis}
      submitFn={(e) => {
        e.preventDefault();
        put(update(pagamento.id).url);
      }}
    />
  );
}
