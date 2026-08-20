import { router, usePage } from '@inertiajs/react';
import { PautaTable } from './components/pauta-table';
import { usePagination } from '@/hooks/use-pagination';
import { ResumoCards } from './components/resumo-cards';

export default function Show({ cursoTutelado, pauta, periodo, filtro }) {
  const { url } = usePage();
  const { handlePageChange } = usePagination('pautas');

  const handlePeriodoChange = (novoPeriodo) => {
    router.visit(url, {
      data: { periodo: novoPeriodo },
      preserveState: false,
      preserveScroll: false,
    });
  };

  const handleFiltro = (novoFiltro) => {
    router.visit(window.location.pathname, {
      data: { periodo, filtro: novoFiltro },
      preserveState: false,
      preserveScroll: false,
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl space-y-6 p-6">
      <ResumoCards
        resumo={pauta.resumo}
        tipo={pauta.tipo}
        filtroActivo={filtro}
        onFiltro={handleFiltro}
      />

      <PautaTable
        data={pauta}
        disciplinas={pauta.disciplinas ?? []}
        alunos={pauta.alunos?.data ?? []}
        pagination={pauta.alunos ?? null}
        periodo={periodo}
        setPeriodo={handlePeriodoChange}
        onPageChange={handlePageChange}
        params={{
          cursoTutelado: cursoTutelado?.id,
          turma: pauta.turma?.id,
        }}
      />
    </div>
  );
}
