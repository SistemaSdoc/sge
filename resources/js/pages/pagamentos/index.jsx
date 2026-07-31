import { useDialog } from '@/hooks/use-dialog';
import { Head, router } from '@inertiajs/react';
import PagamentosTable from './components/pagamentos-table';
import { index, destroy } from '@/actions/App/Http/Controllers/PagamentoController';

export default function Index({ pagamentos, can, filtros, cursosClasses }) {
  const { deleteConfirm } = useDialog();

  const handleDelete = (id) => {
    deleteConfirm({
      title: 'Tens a certeza?',
      description:
        'Esta acção é irreversível. O pagamento será removido permanentemente.',
      confirmLabel: 'Eliminar',
      confirmFn: () => router.delete(destroy(id).url),
    });
  };

  const handlePageChange = (page) => {
    router.visit(index().url, { data: { ...filtros, page }, preserveScroll: true });
  };

  const handleFilterChange = (newFiltros) => {
    router.visit(index().url, { data: newFiltros, preserveScroll: true });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <Head title="Pagamentos" />
      <PagamentosTable
        can={can}
        pagamentos={pagamentos?.data ?? []}
        deleteFn={handleDelete}
        pagination={{
          current_page: pagamentos.current_page,
          last_page: pagamentos.last_page,
        }}
        onPageChange={handlePageChange}
        filtros={filtros}
        cursosClasses={cursosClasses}
        onFilterChange={handleFilterChange}
      />
    </div>
  );
}