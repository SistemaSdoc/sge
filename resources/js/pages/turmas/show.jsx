import { Head } from '@inertiajs/react';
import { TurmaHeader } from './components/show/turma-header';
import { TurmaDetails } from './components/show/turma-details';
import { TurmaAlunos } from './components/show/turma-alunos';

export default function TurmaShow({ turma }) {
  return (
    <>
      <Head title={turma?.nome} />
      <div className="w-full max-w-6xl mx-auto space-y-6">
        <TurmaHeader data={turma} />
        <TurmaDetails data={turma} />
        <TurmaAlunos data={turma} />
      </div>
    </>
  );
}