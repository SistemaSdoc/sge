import { Head, router } from '@inertiajs/react';
import CursoTable from './components/curso-table';
import {
  index,
  destroy,
} from '@/actions/App/Http/Controllers/Tenant/CursosController';
import { useDialog } from '@/hooks/use-dialog';

export default function Index({ cursos, can }) {
  const { deleteConfirm } = useDialog();

  const handleDelete = (cursoId) => {
    deleteConfirm({
      title: 'Tens a certeza?',
      description:
        'Esta acção é irreversível. O curso será eliminado permanentemente.',
      confirmLabel: 'Eliminar',
      confirmFn: () => router.delete(destroy(cursoId).url),
    });
  };

  const handlePageChange = (page) => {
    router.visit(index().url, {
      data: { page },
      preserveScroll: true,
    });
  };

  return (
    <>
      <Head title="Cursos" />

      <CursoTable
        cursos={cursos.data}
        can={can}
        deleteFn={handleDelete}
        pagination={{
          current_page: cursos.current_page,
          last_page: cursos.last_page,
        }}
        onPageChange={handlePageChange}
      />
    </>
  );
}
