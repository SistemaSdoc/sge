import { router } from '@inertiajs/react';
import { TurnoTable } from './components/turno-table';
import { index } from '@/actions/App/Http/Controllers/TurnoController';

export default function Index({ turnos, deleteFn }) {
  const handlePageChange = (page) => {
    router.visit(index().url, {
      data: { page },
      preserveScroll: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl space-y-4 p-6">
      <TurnoTable
        turnos={turnos.data}
        pagination={{
          current_page: turnos.current_page,
          last_page: turnos.last_page,
        }}
        onPageChange={handlePageChange}
        deleteFn={deleteFn}
      />
    </div>
  );
}
