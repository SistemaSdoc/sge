import { useDialog } from '@/hooks/use-dialog';
import { Head, router } from '@inertiajs/react';
import AnoLectivoTable from './components/ano-lectivo-table';
import {
  index,
  destroy,
} from '@/actions/App/Http/Controllers/Tenant/AvisoController';

export default function Index({ anosLectivos = {}, can }) {
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
        can={can}
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
