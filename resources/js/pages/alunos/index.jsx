import { router, usePage } from '@inertiajs/react';
import { AlunoTable } from './components/aluno-table';
import {destroy, index} from '@/actions/App/Http/Controllers/AlunoController';
export default function Index() {
  const { alunos } = usePage().props;
  const handlePageChange = (page) => {
    router.visit(index().url, {
      data: { page },
      preserveScroll: true,
    });
  };

  return (
    <AlunoTable
      data={alunos.data}
      deleteFn={(id) => router.delete(destroy({id: id}))}
      pagination={{
        current_page: alunos.current_page,
        last_page: alunos.last_page,
      }}
      onPageChange={handlePageChange}
    />
  );
}
