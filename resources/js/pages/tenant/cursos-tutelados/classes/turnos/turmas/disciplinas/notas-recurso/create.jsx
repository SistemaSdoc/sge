import { useForm, usePage } from '@inertiajs/react';
import LancamentosRecursoTable from '../notas/components/lancamentos-recurso-table';
import { index as indexRecurso } from '@/actions/App/Http/Controllers/Tenant/NotaDisciplinaRecursoController';
import { usePagination } from '@/hooks/use-pagination';
import { Header } from './components/lancamentos-recurso-header';

export default function Create() {
  const {
    alunos,
    pode_lancar_recurso,
    disciplina,
    can,
    instituicao,
    cursoTutelado,
    cursoClasse,
    cursoClasseTurno,
    turma,
    classeTurnoDisciplina,
  } = usePage().props;
  const alunosPagination = usePagination('alunos');

  // Todos IDs já são primitivos vindos do controller
  const params = {
    instituicao,
    cursoTutelado,
    cursoClasse,
    cursoClasseTurno,
    turma,
    classeTurnoDisciplina,
  };

  const form = useForm({});

  const handleSubmit = (payload) => {
    form.transform(() => payload);
    form.post(
      indexRecurso({ ...params }).url,
      { preserveScroll: true },
    );
  };

  if (!alunos?.data || alunos.data.length === 0) {
    return (
      <div className="flex justify-center py-20">
        <span className="text-sm text-muted-foreground">
          Nenhum aluno em situação de recurso nesta disciplina.
        </span>
      </div>
    );
  }

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6 p-6">
      <Header can={can} disciplina={disciplina} params={params} />

      <LancamentosRecursoTable
        alunos={alunos.data}
        onSubmit={handleSubmit}
        isPending={form.processing}
        podeLancarRecurso={pode_lancar_recurso ?? true}
        pagination={{
          current_page: alunos.current_page,
          last_page: alunos.last_page,
        }}
        onPageChange={alunosPagination.handlePageChange}
      />
    </div>
  );
}