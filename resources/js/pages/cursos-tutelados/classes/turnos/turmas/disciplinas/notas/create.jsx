import { Form, usePage } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import LancamentosTable from './components/lancamentos-table';
import { store } from '@/actions/App/Http/Controllers/NotaDisciplinaController';
import { usePagination } from '@/hooks/use-pagination';

export default function Create({
  instituicao,
  cursoTutelado,
  cursoClasse,
  cursoClasseTurno,
  turma,
  classeTurnoDisciplina,
}) {
  const { data } = usePage().props;
  const alunosPagination = usePagination('alunos');

  if (!data?.alunos || data.alunos.data.length === 0) {
    return (
      <div className="flex justify-center py-20">
        <span className="text-sm text-muted-foreground">
          Sem dados disponíveis.
        </span>
      </div>
    );
  }

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6 p-6">
      <Form
        action={
          store({
            instituicao,
            cursoTutelado,
            cursoClasse,
            cursoClasseTurno,
            turma,
            classeTurnoDisciplina,
          }).url
        }
        method="post"
        options={{ preserveScroll: true }}
      >
        {({ processing }) => (
          <LancamentosTable
            data={data}
            isPending={processing}
            instituicaoId={instituicao}
            cursoId={cursoTutelado}
            classeId={cursoClasse}
            turnoId={cursoClasseTurno}
            turmaId={turma}
            disciplinaId={classeTurnoDisciplina}
            pagination={{
              current_page: data.alunos.current_page,
              last_page: data.alunos.last_page,
            }}
            onPageChange={alunosPagination.handlePageChange}
          />
        )}
      </Form>
    </div>
  );
}
