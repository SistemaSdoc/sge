import { Head, router } from '@inertiajs/react';
import { ClasseTable } from './components/classe-table';
import {
  index,
  destroy,
} from '@/actions/App/Http/Controllers/ClasseController';
import { useDialog } from '@/hooks/use-dialog';

export default function Index({ classes }) {
  const { deleteConfirm } = useDialog();

  const handleDelete = (classeId) => {
    deleteConfirm({
      title: 'Tens a certeza?',
      description:
        'Esta acção é irreversível. A classe será eliminada permanentemente.',
      confirmLabel: 'Eliminar',
      confirmFn: () => router.delete(destroy(classeId).url),
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
      <Head title="Classes" />
      
      <ClasseTable
        classes={classes}
        deleteFn={handleDelete}
        pagination={{
          current_page: classes.current_page,
          last_page: classes.last_page,
        }}
        onPageChange={handlePageChange}
      />
    </>
  );
}
