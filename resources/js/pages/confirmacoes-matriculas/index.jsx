import { router, usePage } from '@inertiajs/react';
import { ConfirmacaoTable } from './components/confirmacao-table';
import {
  index,
  store,
} from '@/actions/App/Http/Controllers/ConfirmacaoMatriculaController';

export default function Index() {
  const { alunos, can } = usePage().props;

  const handlePageChange = (page) => {
    router.visit(index().url, {
      data: { page },
      preserveScroll: true,
    });
  };

  return (
    <ConfirmacaoTable
      data={alunos.data}
      confirmarFn={(id) => router.post(store({ aluno: id }).url)}
      can={can}
      pagination={{
        current_page: alunos.current_page,
        last_page: alunos.last_page,
      }}
      onPageChange={handlePageChange}
    />
  );
}
