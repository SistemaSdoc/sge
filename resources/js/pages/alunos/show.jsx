import { router, usePage } from '@inertiajs/react';
import { AlunoHeader } from './components/show/aluno-header';
import { AlunoSidebar } from './components/show/aluno-sidebar';
import { AlunoActivity } from './components/show/aluno-activity';
import { HistoricoPendenteAlert } from './components/show/historico-pendente-alert';
import { useDialog } from '@/hooks/use-dialog';
import Preencher from '../preencher-historico/components/preencher-modal';

export default function Show() {
  const {
    aluno,
    historicoPendente = [],
    classesFaltando = [],
    anosLectivos = [],
  } = usePage().props;

  const { openForm, closeDialog } = useDialog();

  // Chamado pelo botão "Iniciar" — pendente sem turma_aluno ainda
  const abrirModal = (alunoData, e, pendente) => {
    e.stopPropagation();
    openForm({
      title: 'Preencher Histórico Académico',
      description: '',
      size: 'lg',
      content: (
        <Preencher
          aluno={alunoData}
          // só mostra classes que ainda não têm turma_aluno
          classesFaltando={classesFaltando.filter((c) => !c.em_curso)}
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

      {historicoPendente.length > 0 && (
        <HistoricoPendenteAlert
          aluno={aluno}
          pendentes={historicoPendente}
          abrirSelecaoFn={abrirModal}
        />
      )}

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div className="space-y-6 lg:col-span-1">
          <AlunoSidebar aluno={aluno} />
        </div>
        <div className="space-y-6 lg:col-span-2">
          <AlunoActivity aluno={aluno} />
        </div>
      </div>
    </div>
  );
}