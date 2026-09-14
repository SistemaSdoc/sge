import { useRef } from 'react';
import { Head, router } from '@inertiajs/react';
import {
  destroy,
  update,
} from '@/actions/App/Http/Controllers/Central/CalendarioAnualController';
import { useDialog } from '@/hooks/use-dialog';
import { useDrawerStore } from '@/stores/drawer.store';
import CalendarioForm from './components/calendario-form';
import CalendariosTable from './components/calendarios-table';

export default function Index({ calendarios }) {
  const { deleteConfirm } = useDialog();
  const { openDrawer, closeDrawer } = useDrawerStore();
  const replaceInput = useRef(null);
  const replacingId = useRef(null);

  const replaceFile = (calendario) => {
    replacingId.current = calendario.id;
    replaceInput.current?.click();
  };

  const submitReplacement = (event) => {
    const ficheiro = event.target.files?.[0];

    if (!ficheiro || !replacingId.current) {
      return;
    }

    router.post(
      update(replacingId.current).url,
      { _method: 'patch', ficheiro },
      {
        forceFormData: true,
        onFinish: () => {
          event.target.value = '';
          replacingId.current = null;
        },
      },
    );
  };

  const toggle = (calendario) => {
    const ativo = !calendario.ativo;
    const acao = ativo ? 'activado' : 'desactivado';

    deleteConfirm({
      title: `${ativo ? 'Activar' : 'Desactivar'} calendário?`,
      description: `O calendário de ${calendario.ano} será ${acao} para as instituições.`,
      confirmLabel: ativo ? 'Activar' : 'Desactivar',
      confirmFn: () =>
        router.patch(
          update(calendario.id).url,
          { ativo },
          { preserveScroll: true },
        ),
    });
  };

  const remove = (calendario) => {
    deleteConfirm({
      title: 'Tens a certeza?',
      description:
        'O calendário anual será removido.',
      confirmLabel: 'Remover',
      confirmFn: () => router.delete(destroy(calendario.id).url),
    });
  };

  const handlePageChange = (page) => {
    router.visit(window.location.pathname, {
      data: { page },
      preserveScroll: true,
    });
  };

  return (
    <>
      <Head title="Calendários anuais" />
      <input
        ref={replaceInput}
        type="file"
        accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
        className="hidden"
        onChange={submitReplacement}
      />
      <CalendariosTable
        calendarios={calendarios.data}
        onToggle={toggle}
        onReplace={replaceFile}
        onDelete={remove}
        pagination={calendarios}
        onPageChange={handlePageChange}
        onAdd={() =>
          openDrawer({
            title: 'Adicionar calendário anual',
            description: 'Publique o documento anual para as instituições.',
            content: <CalendarioForm onSuccess={closeDrawer} />,
          })
        }
      />
    </>
  );
}
