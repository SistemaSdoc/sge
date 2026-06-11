import { usePage } from '@inertiajs/react';
import NotasTable from './components/notas-table';

export default function Index() {
  const {
    instituicaoId,
    cursoId,
    classeId,
    turnoId,
    turmaId,
    tdpId,
    disciplina,
    alunos,
  } = usePage().props;

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6 p-6">
      <NotasTable
        alunos={alunos}
        disciplina={disciplina}
        tdpId={tdpId}
        instituicaoId={instituicaoId}
        cursoId={cursoId}
        classeId={classeId}
        turnoId={turnoId}
        turmaId={turmaId}
      />
    </div>
  );
}
