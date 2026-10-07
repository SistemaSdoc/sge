import { Head, router } from '@inertiajs/react';
import { ColegioTable } from './components/colegio-table';

export default function Index({ instituicao, colegios, filters }) {
  const handlePageChange = (page) => {
    router.get(route('colegios.index', {
      ...Object.fromEntries(new URLSearchParams(window.location.search)),
      page,
    }));
  };

  return (
    <>
      <Head title="Turnos" />
      <ColegioTable
        instituicao={instituicao}
        colegios={colegios}
        filters={filters}
        pagination={{
          current_page: colegios.current_page,
          last_page: colegios.last_page,
        }}
        onPageChange={handlePageChange}
      />
    </>
  );
}
