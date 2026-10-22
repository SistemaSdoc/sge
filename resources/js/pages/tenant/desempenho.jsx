import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

import { cn } from '@/lib/utils';
import { Cabecalho } from '@/components/desempenho-aluno/cabecalho';
import { EvolucaoMedia } from '@/components/desempenho-aluno/evolucao-media';
import { EvolucaoAnos } from '@/components/desempenho-aluno/evolucao-anos';
import { VariacaoDisciplinas } from '@/components/desempenho-aluno/variacao-disciplinas';
import { FaltasTrimestre } from '@/components/desempenho-aluno/faltas-trimestre';
import { PontosFortes } from '@/components/desempenho-aluno/pontos-fortes';
import { AMelhorar } from '@/components/desempenho-aluno/a-melhorar';

export default function Desempenho({
  evolucao,
  disciplinas,
  assiduidade,
  filtrosDisponiveis,
  filtrosActivos,
  rotaIndex,
}) {
  const [loading, setLoading] = useState(false);

  const trocarAno = (anoId) => {
    setLoading(true);
    router.get(
      rotaIndex,
      { ano_lectivo_id: anoId },
      {
        preserveScroll: true,
        preserveState: false,
        onFinish: () => setLoading(false),
      },
    );
  };

  return (
    <>
      <Head title="Meu Desempenho" />

      <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-6">
        <Cabecalho
          filtrosDisponiveis={filtrosDisponiveis}
          filtrosActivos={filtrosActivos}
          onTrocarAno={trocarAno}
        />

        <div
          className={cn(
            'grid grid-cols-1 gap-4 lg:grid-cols-2',
            loading && 'pointer-events-none opacity-40',
          )}
        >
          <EvolucaoMedia trimestres={evolucao?.trimestres} />
          <EvolucaoAnos anos={evolucao?.anos} />
          <VariacaoDisciplinas disciplinas={disciplinas} />
          <FaltasTrimestre assiduidade={assiduidade} />
          <PontosFortes disciplinas={disciplinas} />
          <AMelhorar disciplinas={disciplinas} />
        </div>
      </div>
    </>
  );
}
