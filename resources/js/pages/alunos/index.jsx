import { router, usePage } from '@inertiajs/react';
import { AlunoTable } from './components/aluno-table';

export default function Index() {
  const { alunos } = usePage().props;

  return (
    <AlunoTable
      data={alunos}
      deleteFn={(id) => router.delete(`/alunos/${id}`)}
    />
  );
}
