import { router, usePage } from '@inertiajs/react';
import { AlunoHeader } from './components/show/aluno-header';
import { AlunoDetails } from './components/show/aluno-detalhes';
import { AlunoRelated } from './components/show/aluno-related';
import { HistoricoPendenteAlert } from './components/show/historico-pendente-alert';
import { useDialog } from '@/hooks/use-dialog';
import Preencher from '../preencher-historico/components/preencher-modal';

export default function Show() {
  const {
    aluno,
    historicoPendente = [],
    classesFaltando = [],
    anosLectivos = [],
    turnos = [],
    turmasPorTurno = [],
  } = usePage().props;

  const { openForm, closeDialog } = useDialog();

  const abrirSelecaoAnoDialog = (alunoData, e) => {
    e.stopPropagation();

    openForm({
      title: 'Preencher Histórico Escolar',
      description: `Aluno: ${alunoData.nome}`,
      size: 'lg',
      content: (
        <Preencher
          aluno={alunoData}
          classesFaltando={classesFaltando}
          anosLectivos={anosLectivos}
          onCancel={() => closeDialog()}
          onSuccess={() => {
            closeDialog();
            router.reload();
          }}
        />
      ),
    });
  };

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6 p-6">
      <AlunoHeader aluno={aluno} />
      <AlunoDetails aluno={aluno} />
      <AlunoRelated aluno={aluno} />
      {historicoPendente.length > 0 && (
        <HistoricoPendenteAlert
          aluno={aluno}
          pendentes={historicoPendente}
          abrirSelecaoFn={abrirSelecaoAnoDialog}
        />
      )}
    </div>
  );
}
