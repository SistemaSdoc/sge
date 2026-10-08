import { Head, router } from '@inertiajs/react';
import ItensTable from './components/itens-table';
import { index } from '@/actions/App/Http/Controllers/Tenant/ItemPagavelController';

export default function Index({ itens, can, filters }) {
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
    <div className="mx-auto w-full max-w-7xl p-4 md:p-6">
      <Head title="Itens pagáveis" />

      <ItensTable
        itens={itens?.data ?? []}
        can={can}
        filters={filters}
        pagination={itens}
        onPageChange={handlePageChange}
      />
    </div>
  );
}
