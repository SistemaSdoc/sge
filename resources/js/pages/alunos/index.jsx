import { router, usePage } from '@inertiajs/react';
import { AlunoTable } from './components/aluno-table';
import { destroy, index } from '@/actions/App/Http/Controllers/AlunoController';

export default function Index() {
  const { alunos, can, anoLectivoId, anosLectivos } = usePage().props;

  const handlePageChange = (page) => {
    router.visit(index().url, {
      data: { page, ano_lectivo_id: anoLectivoId },
      preserveScroll: true,
    });
  };

  const handleAnoLectivoChange = (value) => {
    router.visit(index().url, {
      data: { ano_lectivo_id: value },
      preserveScroll: true,
    });
  };

  return (
    <AlunoTable
      data={alunos.data}
      deleteFn={(id) => router.delete(destroy({ id: id }))}
      can={can}
      pagination={{
        current_page: alunos.current_page,
        last_page: alunos.last_page,
      }}
      onPageChange={handlePageChange}
      anoLectivoActual={anoLectivoId}
      anosLectivos={anosLectivos}
      onAnoLectivoChange={handleAnoLectivoChange}
    />
  );
}
