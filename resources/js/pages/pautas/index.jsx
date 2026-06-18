import { useState, useEffect } from 'react';
import { usePage, router } from '@inertiajs/react';
import { PautaTable } from './components/pauta-table';
import { Button } from '@/components/ui/button';
import { ChevronLeft } from 'lucide-react';
import { indexTurmas } from '@/actions/App/Http/Controllers/PautaController';

export default function Show({
  cursoTutelado,
  turma,
  pauta,
  periodo: initialPeriodo,
}) {
  const [periodo, setPeriodo] = useState(initialPeriodo || '1');
  const [pautaData, setPautaData] = useState(pauta);
  const page = usePage();

  useEffect(() => {
    if (periodo !== initialPeriodo) {
      // Navega para a mesma URL com o novo período como query parameter
      router.get(
        window.location.pathname,
        { periodo },
        {
          preserveState: false,
          onSuccess: (page) => {
            setPautaData(page.props.pauta);
          },
        },
      );
    }
  }, [periodo, initialPeriodo]);

  const handlePeriodoChange = (novoPeriodo) => {
    setPeriodo(novoPeriodo);
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <PautaTable
        data={pautaData}
        disciplinas={pautaData?.disciplinas ?? []}
        alunos={pautaData?.alunos ?? []}
        periodo={periodo}
        turmaId={turma?.id}
        setPeriodo={handlePeriodoChange}
        cursoTuteladoId={cursoTutelado?.id}
      />
    </div>
  );
}
