import { usePage } from '@inertiajs/react';
import NotasTable from './components/notas-table';

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
  } = usePage().props;

  console.log('Index props:', {
    instituicao,
    cursoTutelado,
    cursoClasse,
    cursoClasseTurno,
    turma,
    disciplina,
    tdp,
    alunos,
  });

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
        turma={turma}
      />
    </div>
  );
}
