import { Head } from '@inertiajs/react';
import { PagamentosForm } from './components/pagamentos-form';

export default function Create({
  alunos,
  itensPagaveis,
  paidRecord,
  pendenciasComMulta,
}) {
  return (
    <>
      <Head title="Novo Pagamento" />
      <PagamentosForm
        alunos={alunos}
        itensPagaveis={itensPagaveis}
        paidRecord={paidRecord}
        pendenciasComMulta={pendenciasComMulta}
      />
    </>
  );
}