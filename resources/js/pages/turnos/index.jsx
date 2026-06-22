import { Head, router } from '@inertiajs/react';
import { TurnoTable } from './components/turno-table';
import { index, destroy } from '@/actions/App/Http/Controllers/TurnoController';
import { useDialog } from '@/hooks/use-dialog';

export default function Index({ turnos }) {
  const { deleteConfirm } = useDialog();

  const handleDelete = (turnoId) => {
    deleteConfirm({
      title: 'Tens a certeza?',
      description:
        'Esta acção é irreversível. O turno será eliminado permanentemente.',
      confirmLabel: 'Eliminar',
      confirmFn: () => router.delete(destroy(turnoId).url),
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
      <Head title="Turnos" />

      <TurnoTable
        turnos={turnos.data}
        pagination={{
          current_page: turnos.current_page,
          last_page: turnos.last_page,
        }}
        onPageChange={handlePageChange}
        deleteFn={handleDelete}
      />
    </>
  );
}
