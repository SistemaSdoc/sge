import { router } from '@inertiajs/react';
import { ProfessorTable } from './components/professor-table';
import {
  index,
  destroy,
} from '@/actions/App/Http/Controllers/Tenant/ProfessorController';
import { useDialog } from '@/hooks/use-dialog';

export default function Index({ professores, filters }) {
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
      data: {
        ...Object.fromEntries(new URLSearchParams(window.location.search)),
        page,
      },
      preserveScroll: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-4 md:p-6">
      <ProfessorTable
        pagination={{
          current_page: professores.current_page,
          last_page: professores.last_page,
        }}
        onPageChange={handlePageChange}
        professores={professores}
        filters={filters}
        deleteFn={handleDelete}
      />
    </div>
  );
}
