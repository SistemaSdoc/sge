import { Head, router } from '@inertiajs/react';
import ItensTable from '../itens-pagaveis/components/itens-table';
import { index } from '@/actions/App/Http/Controllers/ItemPagavelController';

export default function Index({ itens, can }) {
  const handlePageChange = (page) => {
    router.visit(index().url, {
      data: { page },
      preserveScroll: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <Head title="Itens pagáveis" />

      <ItensTable
        itens={itens?.data ?? []}
        can={can}
        pagination={itens}
        onPageChange={handlePageChange}
      />
    </div>
  );
}
