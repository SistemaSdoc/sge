import { router, usePage } from '@inertiajs/react';
import { AlunoHeader } from './components/show/aluno-header';
import { AlunoDetails } from './components/show/aluno-detalhes';
import { AlunoRelated } from './components/show/aluno-related';
import { HistoricoPendenteAlert } from './components/show/historico-pendente-alert';

export default function Show() {
  const { aluno, historicoPendente = [] } = usePage().props;

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6 p-6">
      <AlunoHeader aluno={aluno} />
      <AlunoDetails aluno={aluno} />
      <AlunoRelated aluno={aluno} />
      {historicoPendente.length > 0 && (
        <HistoricoPendenteAlert
          aluno={aluno}
          pendentes={historicoPendente}
        />
      )}
    </div>
  );
}
