import { router, usePage } from '@inertiajs/react';
import { useCallback, useEffect } from 'react';
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
  const abrirModal = useCallback(
    (alunoData, e, pendente) => {
      e?.stopPropagation?.();
      openForm({
        title: 'Preencher Histórico Académico',
        description: '',
        size: 'lg',
        content: (
          <Preencher
            aluno={alunoData}
            classesFaltando={classesFaltando.filter((c) => !c.em_curso)}
            cursoClasseIdInicial={pendente?.curso_classe_id ?? null}
            anosLectivos={anosLectivos}
            onCancel={() => closeDialog()}
            onSuccess={() => {
              closeDialog();
              router.reload();
            }}
          />
        ),
      });
    },
    [anosLectivos, classesFaltando, closeDialog, openForm],
  );

  useEffect(() => {
    const url = new URL(window.location.href);

    if (url.searchParams.get('iniciar_historico') !== '1') {
      return;
    }

    const pendente = classesFaltando.find((classe) => !classe.em_curso);

    if (!pendente) {
      return;
    }

    url.searchParams.delete('iniciar_historico');
    window.history.replaceState(window.history.state, '', `${url.pathname}${url.search}`);
    abrirModal(aluno, null, pendente);
  }, [aluno, abrirModal, classesFaltando]);

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6 p-4 md:p-6">
      <AlunoHeader aluno={aluno} />

      {aluno.can?.manageHistorico && historicoPendente.length > 0 && (
        <HistoricoPendenteAlert
          aluno={aluno}
          pendentes={historicoPendente}
          iniciarHistoricoFn={(url) => router.visit(url)}
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
