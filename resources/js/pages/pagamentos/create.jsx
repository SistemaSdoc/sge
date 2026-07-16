import { PagamentosForm } from './components/pagamentos-form';

export default function Page({ alunos, itensPagaveis, paidRecord }) {
  return (
    <PagamentosForm
      alunos={alunos}
      itensPagaveis={itensPagaveis}
      paidRecord={paidRecord}
    />
  );
}
