import { useDialog } from '@/hooks/use-dialog';
import { Head, router } from '@inertiajs/react';
import ItensTable from './components/itens-table';
import {
  index,
  destroy,
} from '@/actions/App/Http/Controllers/ItemPagavelController';

export default function Index({ itens, can }) {
  const { deleteConfirm } = useDialog();

  const handleDelete = (itemId) => {
    deleteConfirm({
      title: 'Tens a certeza?',
      description:
        'Esta ação é irreversível. O item será removido permanentemente.',
      confirmLabel: 'Eliminar',
      confirmFn: () => router.delete(destroy(itemId).url),
    });
  };

  const handlePageChange = (page) => {
    router.visit(index().url, {
      data: { page },
      preserveScroll: true,
    });
  };

  const items = Array.isArray(itens) ? itens : (itens?.data ?? []);
  const pagination =
    !Array.isArray(itens) && itens
      ? { current_page: itens.current_page, last_page: itens.last_page }
      : {};

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <Head title="Itens pagáveis" />

      <ItensTable
        itens={items}
        can={can}
        deleteFn={handleDelete}
        pagination={pagination}
        onPageChange={handlePageChange}
      />
    </div>
  );
}
