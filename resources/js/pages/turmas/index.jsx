import { TurmaTable } from './components/turma-table';
import { Head, router } from '@inertiajs/react';
import { index } from '@/actions/App/Http/Controllers/TurmaController';
import { TurmaForm } from './components/turma-form';
import { useDrawer } from '@/hooks/use-drawer';

export default function Index({
  turmas,
  can,
  anosLectivos = [],
  anoLectivoActual,
  cursos = [],
  classes = [],
  instituicaoId,
}) {
  const { openForm, closeDrawer } = useDrawer();

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
      title: 'Criar Nova Turma',
      description: 'Preenche os dados para criar uma nova turma',
      content: (
        <TurmaForm
          instituicaoId={instituicaoId}
          cursos={cursos}
          classes={classes}
          onSuccess={() => {
            closeDrawer();
            router.reload({ only: ['turmas'] });
          }}
          closeDrawer={closeDrawer}
        />
      ),
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <Head title="Turmas" />

      <TurmaTable
        can={can}
        turmas={turmas.data ?? []}
        pagination={turmas}
        anosLectivos={anosLectivos}
        onPageChange={handlePageChange}
        anoLectivoActual={anoLectivoActual}
        onAnoLectivoChange={handleAnoLectivoChange}
        handleAdicionarTurma={handleAdicionarTurma}
      />
    </div>
  );
}
