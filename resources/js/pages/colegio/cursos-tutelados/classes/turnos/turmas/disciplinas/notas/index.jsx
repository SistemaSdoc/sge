import { usePage } from '@inertiajs/react';
import NotasTable from './components/notas-table';
import { usePagination } from '@/hooks/use-pagination';

export default function Index() {
  const {
    instituicao,
    cursoTutelado,
    cursoClasse,
    cursoClasseTurno,
    turma,
    disciplina,
    tdp,
    alunos,
    can,
    periodos_disponiveis,
    todos_disponiveis,
  } = usePage().props;
  const alunosPagination = usePagination('alunos');

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6 p-6">
      <NotasTable
        alunos={alunos}
        disciplina={disciplina}
        tdpId={tdp}
        instituicao={instituicao}
        cursoTutelado={cursoTutelado}
        cursoClasse={cursoClasse}
        cursoClasseTurno={cursoClasseTurno}
        pagination={{
          current_page: alunos.current_page,
          last_page: alunos.last_page,
        }}
        onPageChange={alunosPagination.handlePageChange}
        turma={turma}
        can={can}
        periodosDisponiveis={periodos_disponiveis}
        todosDisponiveis={todos_disponiveis}
      />
    </div>
  );
}
