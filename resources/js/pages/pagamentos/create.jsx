import { useForm } from '@inertiajs/react';
import { PagamentosForm } from './components/pagamentos-form';
import { store } from '@/actions/App/Http/Controllers/AvisoController';

export default function Create({ alunos = [], itensPagaveis = [] }) {
  const { post, data, setData, processing, errors } = useForm({
    aluno_id: '',
    estudante: '',
    referencia: '',
    valor: '',
    tipo: 'propina',
    metodo: 'multicaixa',
    estado: 'pendente',
    descricao: '',
    data: '',
    observacoes: '',
    itens: [],
    valor_total: 0,
  });

  return (
    <PagamentosForm
      title="Registar pagamento"
      submitLabel="Guardar pagamento"
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      alunos={alunos}
      itensPagaveis={itensPagaveis}
      submitFn={(e) => {
        e.preventDefault();
        post('/dashboard/pagamentos');
      }}
    />
  );
}
