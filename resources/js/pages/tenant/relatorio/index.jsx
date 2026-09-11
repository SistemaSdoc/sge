// resources/js/pages/tenant/relatorio/index.jsx
import { Head, router } from '@inertiajs/react';

import ResumoGeral from './resumo-geral';

export default function RelatorioIndex({
  tipo,
  filtros,
  stats,
  tabela,
  graficos,
  kpis_extra: kpisExtra,
}) {
  function irPara(novosFiltros) {
    router.get(route('relatorios.index'), { ...filtros, ...novosFiltros }, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  }

  return (
    <>
      <Head title="Relatórios" />

      <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
        {tipo === 'geral' ? (
          <ResumoGeral stats={stats} graficos={graficos} kpis_extra={kpisExtra} />
        ) : (
          <pre className="overflow-auto rounded-none bg-muted p-4 text-xs">
            {JSON.stringify({ stats, tabela }, null, 2)}
          </pre>
        )}
      </div>
    </>
  );
}

RelatorioIndex.layout = {
  breadcrumbs: [
    {
      title: 'Relatórios',
      href: '/relatorios',
    },
  ],
};