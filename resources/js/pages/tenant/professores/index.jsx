import { router } from '@inertiajs/react';
import { ProfessorTable } from './components/professor-table';
import {
  index,
  destroy,
} from '@/actions/App/Http/Controllers/Tenant/ProfessorController';
import { useDialog } from '@/hooks/use-dialog';

export default function Index({ professores }) {
  const { deleteConfirm } = useDialog();

  const handleDelete = (professorId) => {
    deleteConfirm({
      title: 'Tens a certeza?',
      description:
        'Esta acção é irreversível. O professor será eliminado permanentemente.',
      confirmLabel: 'Eliminar',
      confirmFn: () => router.delete(destroy(professorId).url),
    });
  };

  const handlePageChange = (page) => {
    router.visit(index().url, {
      data: { page },
      preserveScroll: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <ProfessorTable
        pagination={{
          current_page: professores.current_page,
          last_page: professores.last_page,
        }}
        onPageChange={handlePageChange}
        professores={professores}
        deleteFn={handleDelete}
      />
    </div>
  );
}
