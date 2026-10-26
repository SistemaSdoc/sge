import { useDrawer } from '@/hooks/use-drawer';
import { Head, router } from '@inertiajs/react';
import { TurmaForm } from './components/turma-form';
import { TurmaTable } from './components/turma-table';
import { index } from '@/actions/App/Http/Controllers/Tenant/TurmaController';
import { destroy } from '@/actions/App/Http/Controllers/Tenant/ClasseTurnoTurmaController';
import { useDialog } from '@/hooks/use-dialog';
import { toast } from 'sonner';

export default function Index({
  can,
  turmas,
  filters,
  cursos = [],
  classes = [],
  instituicaoId,
  anoLectivoActual,
  anosLectivos = [],
}) {
  const { openForm, closeDrawer } = useDrawer();
  const { deleteConfirm } = useDialog();

  const handleRemoverTurma = (turma) => {
    deleteConfirm({
      title: 'Tens a certeza?',
      description: 'Esta acção é irreversível. A turma será removida.',
      confirmLabel: 'Remover',
      confirmFn: () =>
        router.delete(destroy(turma).url, {
          onError: (errors) =>
            toast.error(errors.turma ?? 'Não foi possível remover a turma.'),
        }),
    });
  };

  const handlePageChange = (page) => {
    router.visit(index().url, {
      data: { page, ano_lectivo_id: anoLectivoActual },
      preserveScroll: true,
      preserveState: true,
    });
  };

  const handleAnoLectivoChange = (value) => {
    router.visit(index().url, {
      data: { ano_lectivo_id: value },
      preserveScroll: true,
      preserveState: true,
    });
  };

  const handleAdicionarTurma = () => {
    openForm({
      title: 'Criar nova turma',
      description: 'Preenche os dados para criar uma nova turma',
      content: (
        <TurmaForm
          instituicaoId={instituicaoId}
          closeDrawer={closeDrawer}
          classes={classes}
          cursos={cursos}
          onSuccess={() => {
            closeDrawer();
            router.reload({ only: ['turmas'] });
          }}
        />
      ),
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-4 md:p-6">
      <Head title="Turmas" />

      <TurmaTable
        can={can}
        pagination={turmas}
        turmas={turmas.data ?? []}
        filters={filters}
        anosLectivos={anosLectivos}
        onPageChange={handlePageChange}
        anoLectivoActual={anoLectivoActual}
        onAnoLectivoChange={handleAnoLectivoChange}
        handleAdicionarTurma={handleAdicionarTurma}
        deleteFn={handleRemoverTurma}
      />
    </div>
  );
}
