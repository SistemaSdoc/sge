import { Head, router } from '@inertiajs/react';
import { ClasseTable } from './components/classe-table';
import {
  index,
  destroy,
} from '@/actions/App/Http/Controllers/Tenant/ClasseController';
import { useDialog } from '@/hooks/use-dialog';

export default function Index({ classes, can, filters }) {
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
      data: {
        ...Object.fromEntries(new URLSearchParams(window.location.search)),
        page,
      },
      preserveScroll: true,
    });
  };

  return (
    <>
      <Head title="Classes" />
      <ClasseTable
        classes={classes}
        can={can}
        filters={filters}
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
