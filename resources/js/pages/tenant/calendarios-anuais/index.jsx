import { Head, router } from '@inertiajs/react';
import { index } from '@/actions/App/Http/Controllers/Tenant/CalendarioAnualController';
import CalendariosTable from './components/calendarios-table';

export default function Index({ calendarios }) {
  const handlePageChange = (page) => {
    router.visit(index().url, {
      data: { page },
      preserveScroll: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <Head title="Calendários anuais" />

      <CalendariosTable
        calendarios={calendarios.data}
        pagination={calendarios}
        onPageChange={handlePageChange}
      />
    </div>
  );
}
