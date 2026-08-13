import { useDrawer } from '@/hooks/use-drawer';
import { Head, router } from '@inertiajs/react';
import { TurmaForm } from './components/turma-form';
import { TurmaTable } from './components/turma-table';
import { index } from '@/actions/App/Http/Controllers/TurmaController';

export default function Index({
  can,
  turmas,
  cursos = [],
  classes = [],
  instituicaoId,
  anoLectivoActual,
  anosLectivos = [],
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
    <div className="mx-auto w-full max-w-7xl p-6">
      <Head title="Turmas" />

      <TurmaTable
        can={can}
        pagination={turmas}
        turmas={turmas.data ?? []}
        anosLectivos={anosLectivos}
        onPageChange={handlePageChange}
        anoLectivoActual={anoLectivoActual}
        onAnoLectivoChange={handleAnoLectivoChange}
        handleAdicionarTurma={handleAdicionarTurma}
      />
    </div>
  );
}
