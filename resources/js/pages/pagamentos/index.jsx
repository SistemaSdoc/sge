import { useDialog } from '@/hooks/use-dialog';
import { Head, router } from '@inertiajs/react';
import PagamentosTable from './components/pagamentos-table';
import { index, destroy } from '@/actions/App/Http/Controllers/AvisoController';

export default function Index({ pagamentos, can }) {
  const { deleteConfirm } = useDialog();

  const paymentItems = Array.isArray(pagamentos)
    ? pagamentos
    : (pagamentos?.data ?? []);

  const pagination =
    !Array.isArray(pagamentos) && pagamentos
      ? {
          current_page: pagamentos.current_page ?? 1,
          last_page: pagamentos.last_page ?? 1,
        }
      : {};

  const handleDelete = (paymentId) => {
    deleteConfirm({
      title: 'Tens a certeza?',
      description:
        'Esta acção é irreversível. O pagamento será removido permanentemente.',
      confirmLabel: 'Eliminar',
      confirmFn: () => router.delete(destroy(paymentId).url),
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
      <Head title="Pagamentos" />

      <PagamentosTable
        can={can}
        pagamentos={paymentItems}
        deleteFn={handleDelete}
        pagination={pagination}
        onPageChange={handlePageChange}
      />
    </div>
  );
}
