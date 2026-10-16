import { router } from '@inertiajs/react';
import { useState } from 'react';
import { NotasTable } from './components/notas-table';
import { NotasResumo } from './components/notas-resumo';

export default function Index({
  notas,
  resultadoFinal,
  classes = [],
  classeId,
}) {
  const [periodo, setPeriodo] = useState('1');

  const handleClasseChange = (value) => {
    const params = new URLSearchParams(window.location.search);
    params.set('classe_id', value);
    router.get(window.location.pathname, Object.fromEntries(params.entries()), {
      preserveState: true,
      replace: true,
    });
  };

  return (
    <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-6">
      <NotasResumo
        data={notas ?? []}
        resultadoFinal={resultadoFinal}
        periodo={periodo}
      />
      <NotasTable
        data={notas ?? []}
        resultadoFinal={resultadoFinal}
        classes={classes}
        classeId={classeId}
        handleClasseChange={handleClasseChange}
        periodo={periodo}
        onPeriodoChange={setPeriodo}
      />
    </div>
  );
}
