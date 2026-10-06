import { Head, router } from '@inertiajs/react';
import { TurnoTable } from './components/turno-table';
import {
  index,
  destroy,
} from '@/actions/App/Http/Controllers/Tenant/TurnoController';
import { useDialog } from '@/hooks/use-dialog';

export default function Index({ turnos, can, filters }) {
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
      data: { ...Object.fromEntries(new URLSearchParams(window.location.search)), page },
      preserveScroll: true,
    });
  };

  return (
    <>
      <Head title="Turnos" />
      <TurnoTable
        turnos={turnos}
        can={can}
        filters={filters}
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
