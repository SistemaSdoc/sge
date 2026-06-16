import { router, usePage } from '@inertiajs/react';
import { AlunoTable } from './components/aluno-table';

export default function Index() {
  const { alunos } = usePage().props;
  const handlePageChange = (page) => {
    router.visit('/alunos', {
      data: { page },
      preserveScroll: true,
    });
  };

  return (
    <AlunoTable
      data={alunos.data}
      deleteFn={(id) => router.delete(`/alunos/${id}`)}
      pagination={{
        current_page: alunos.current_page,
        last_page: alunos.last_page,
      }}
      onPageChange={handlePageChange}
    />
  );
}
