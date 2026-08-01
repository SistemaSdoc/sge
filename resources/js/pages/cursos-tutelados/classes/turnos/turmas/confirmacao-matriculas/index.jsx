import { router, usePage } from '@inertiajs/react';
import { ConfirmacaoTable } from './components/confirmacao-table';
import { index } from '@/actions/App/Http/Controllers/ConfirmacaoMatriculaController';
import { useDialog } from '@/hooks/use-dialog';
import { ConfirmarMatriculaModal } from './components/confirmar-matricula-modal';

export default function Index() {
  const { alunos, turma, params, anosLectivos, turmasPorAno } = usePage().props;
  const { openForm, closeDialog } = useDialog();

  const handlePageChange = (page) => {
    router.visit(index(params).url, {
      data: { page },
      only: ['alunos'],
      preserveScroll: true,
      preserveState: true,
    });
  };

  const abrirConfirmacaoMatriculaDialog = (aluno, e) => {
    e.stopPropagation();

    openForm({
      title: `Confirmar Matrícula - ${aluno.nome}`,
      description: '',
      size: 'lg',
      content: (
        <ConfirmarMatriculaModal
          aluno={aluno}
          params={params}
          anosLectivos={anosLectivos}
          turmasPorAno={turmasPorAno}
          onCancel={() => closeDialog()}
          onSuccess={() => closeDialog()}
        />
      ),
    });
  };

  return (
    <ConfirmacaoTable
      data={alunos.data}
      onConfirmar={abrirConfirmacaoMatriculaDialog}
      turma={turma}
      pagination={{
        current_page: alunos.current_page,
        last_page: alunos.last_page,
        total: alunos.total,
      }}
      onPageChange={handlePageChange}
    />
  );
}
