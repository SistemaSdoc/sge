import { router, usePage } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import { AlunoTable } from './components/aluno-table';
import { AtribuirTurmaForm } from './components/atribuir-turma-form';
import { useDialog } from '@/hooks/use-dialog';
import { index } from '@/actions/App/Http/Controllers/Tenant/AlunoController';
import { atribuirTurma } from '@/actions/App/Http/Controllers/Tenant/TurmaController';

export default function Index() {
  const { alunos, can, anoLectivoId, anosLectivos, filters } = usePage().props;
  const { openForm, closeDialog } = useDialog();
  const { post, data, setData, processing, errors } = useForm({
    turma_id: '',
  });

  const handlePageChange = (page) => {
    router.visit(index().url, {
      data: { ...Object.fromEntries(new URLSearchParams(window.location.search)), page, ano_lectivo_id: anoLectivoId },
      preserveScroll: true,
    });
  };

  const handleAnoLectivoChange = (value) => {
    const params = Object.fromEntries(new URLSearchParams(window.location.search));
    delete params.page;

    router.visit(index().url, {
      data: { ...params, ano_lectivo_id: value },
      preserveScroll: true,
    });
  };

  const handleAtribuirTurma = (aluno, e) => {
    e?.stopPropagation();

    openForm({
      title: 'Atribuir Turma',
      description: `Selecione uma turma para ${aluno?.nome}`,
      content: (
        <AtribuirTurmaForm
          data={data}
          setData={setData}
          errors={errors}
          processing={processing}
          submitFn={(turmaId) => {
            post(atribuirTurma(aluno.id).url, {
              data: { turma_id: turmaId },
              onSuccess: () => closeDialog(),
            });
          }}
        />
      ),
    });
  };

  return (
    <>
    <AlunoTable
      data={alunos.data}
      can={can}
      pagination={alunos}
      onPageChange={handlePageChange}
      anoLectivoActual={anoLectivoId}
      anosLectivos={anosLectivos}
      onAnoLectivoChange={handleAnoLectivoChange}
      atribuirTurmaFn={handleAtribuirTurma}
      filters={filters}
    />
    </>
  );
}
