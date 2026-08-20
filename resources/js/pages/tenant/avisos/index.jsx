import { useDialog } from '@/hooks/use-dialog';
import { Head, router } from '@inertiajs/react';
import AvisosTable from './components/aviso-table';
import { index, destroy } from '@/actions/App/Http/Controllers/Tenant/AvisoController';

export default function Index({ avisos, can }) {
  const { deleteConfirm } = useDialog();

  const handleDelete = (avisoId) => {
    deleteConfirm({
      title: 'Tens a certeza?',
      description:
        'Esta acção é irreversível. O aviso será eliminado permanentemente.',
      confirmLabel: 'Eliminar',
      confirmFn: () => router.delete(destroy(avisoId).url),
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
      <Head title="Avisos" />

      <AvisosTable
        can={can}
        avisos={avisos.data}
        deleteFn={handleDelete}
        pagination={{
          current_page: avisos.current_page,
          last_page: avisos.last_page,
        }}
        onPageChange={handlePageChange}
      />
    </div>
  );
}
