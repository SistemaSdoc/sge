import { TurmaTable } from './components/turma-table';
import { Head, router } from '@inertiajs/react';
import { index } from '@/actions/App/Http/Controllers/TurmaController';

export default function Index({
  turmas,
  can,
  anosLectivos = [],
  anoLectivoActual,
}) {
  const handlePageChange = (page) => {
    router.visit(index().url, {
      data: { page, ano_lectivo_id: anoLectivoActual },
      preserveScroll: true,
    });
  };

  const handleAnoLectivoChange = (value) => {
    router.visit(index().url, {
      data: { ano_lectivo_id: value },
      preserveScroll: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <Head title="Turmas" />

      <TurmaTable
        turmas={turmas.data ?? []}
        can={can}
        anosLectivos={anosLectivos}
        anoLectivoActual={anoLectivoActual}
        onAnoLectivoChange={handleAnoLectivoChange}
        pagination={{
          current_page: turmas.current_page,
          last_page: turmas.last_page,
        }}
        onPageChange={handlePageChange}
      />
    </div>
  );
}
