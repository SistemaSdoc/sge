import { Head, router } from '@inertiajs/react';
import AnoLectivoTable from './components/ano-lectivo-table';
import { index } from '@/actions/App/Http/Controllers/Tenant/AnoLectivoController';

export default function Index({ anosLectivos = {} }) {
  const handlePageChange = (page) => {
    router.visit(index().url, {
      data: { page },
      preserveScroll: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <Head title="Anos Lectivos" />

      <AnoLectivoTable
        anosLectivos={anosLectivos.data}
        pagination={{
          current_page: anosLectivos.current_page,
          last_page: anosLectivos.last_page,
        }}
        onPageChange={handlePageChange}
      />
    </div>
  );
}
