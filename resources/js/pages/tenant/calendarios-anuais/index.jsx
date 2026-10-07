import { Head, router } from '@inertiajs/react';
import { index } from '@/actions/App/Http/Controllers/Tenant/CalendarioAnualController';
import CalendariosTable from './components/calendarios-table';

export default function Index({ calendarios, filters }) {
  const handlePageChange = (page) => {
    router.visit(index().url, {
      data: { ...Object.fromEntries(new URLSearchParams(window.location.search)), page },
      preserveScroll: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <Head title="Calendários anuais" />
      <CalendariosTable
        calendarios={calendarios.data}
        filters={filters}
        pagination={calendarios}
        onPageChange={handlePageChange}
      />
    </div>
  );
}
