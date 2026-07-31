import { router, usePage } from '@inertiajs/react';

import { update } from '@/routes/inscricoes';
import { InscricaoTable } from './components/inscricao-table';

export default function Index() {
  const { inscricoes, anosLectivos, anoLectivoActual, can } = usePage().props;

  const handlePageChange = (page) => {
    router.visit('/dashboard/inscricoes', {
      data: { page, ano_lectivo_id: anoLectivoActual },
      preserveScroll: true,
    });
  };

  const handleAnoLectivoChange = (value) => {
    router.visit('/dashboard/inscricoes', {
      data: { ano_lectivo_id: value },
      preserveScroll: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <InscricaoTable
        inscricoes={inscricoes.data}
        can={can}
        pagination={{
          current_page: inscricoes.current_page,
          last_page: inscricoes.last_page,
        }}
        onPageChange={handlePageChange}
        updateFn={(id, nota_teste) =>
          router.patch(update.url(id), { nota_teste })
        }
        anoLectivoActual={anoLectivoActual}
        anosLectivos={anosLectivos}
        onAnoLectivoChange={handleAnoLectivoChange}
      />
    </div>
  );
}
