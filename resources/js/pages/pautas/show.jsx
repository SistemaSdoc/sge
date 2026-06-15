import { useState, useEffect } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import { PautaTable } from './components/pauta-table';
import { Button } from '@/components/ui/button';
import { ChevronLeft } from 'lucide-react';

export default function Show({
  instituicao,
  cursoTutelado,
  cursoClasse,
  cursoClasseTurno,
  turma,
  pauta,
  periodo: initialPeriodo,
}) {
  const [periodo, setPeriodo] = useState(initialPeriodo || '1');
  const [pautaData, setPautaData] = useState(pauta);
  const page = usePage();
  console.log({
    instituicao,
    cursoTutelado,
    cursoClasse,
    cursoClasseTurno,
    turma,
  });

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
    <div className="mx-auto w-full max-w-7xl px-4 py-6">
      <div className="mb-6">
        <Link
          href={`/instituicoes/${instituicao?.id}/cursos-tutelados/${cursoTutelado?.id}/pautas`}
        >
          <Button variant="ghost" size="sm" className="mb-2">
            <ChevronLeft className="mr-1 size-4" />
            Voltar
          </Button>
        </Link>
        <h1 className="mt-2 text-2xl font-bold">
          {turma?.nome}
          <p>
            {turma?.classe} — {turma?.turno}
          </p>
        </h1>
        <p className="text-sm text-muted-foreground">{turma?.curso?.nome}</p>
      </div>

      <PautaTable
        data={pautaData}
        disciplinas={pautaData?.disciplinas ?? []}
        alunos={pautaData?.alunos ?? []}
        periodo={periodo}
        turmaId={turma?.id}
        setPeriodo={handlePeriodoChange}
        instituicaoId={instituicao?.id}
        cursoTuteladoId={cursoTutelado?.id}
        cursoClasseId={cursoClasse?.id}
        cursoClasseTurnoId={cursoClasseTurno?.id}
      />
    </div>
  );
}
