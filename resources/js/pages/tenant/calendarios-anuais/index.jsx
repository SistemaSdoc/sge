import { useRef } from 'react';
import { Head, router } from '@inertiajs/react';
import {
  destroy,
  update,
} from '@/actions/App/Http/Controllers/Central/CalendarioAnualController';
import { useDialog } from '@/hooks/use-dialog';
import { useDrawerStore } from '@/stores/drawer.store';
import CalendariosTable from './components/calendarios-table';

export default function Index({ calendarios }) {
  const { deleteConfirm } = useDialog();
  const { openDrawer, closeDrawer } = useDrawerStore();
  const maxFileSize = 10 * 1024 * 1024;
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

    if (ficheiro.size > maxFileSize) {
      window.alert('O ficheiro não pode ultrapassar 10 MB.');
      event.target.value = '';

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

  const toggle = (calendario, ativo) => {
    router.patch(
      update(calendario.id).url,
      { ativo },
      { preserveScroll: true },
    );
  };

  const handlePageChange = (page) => {
    router.visit(window.location.pathname, {
      data: { page },
      preserveScroll: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
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
        pagination={calendarios}
        onPageChange={handlePageChange}
      />
    </div>
  );
}
