import { TurmaTable } from './components/turma-table';
import { Head } from '@inertiajs/react';

export default function TurmaIndex({ turmas }) {
  console.log({
    turmas,
  });
  return (
    <>
      <Head title="Turmas" />
      <TurmaTable turmas={turmas.data ?? []} deleteFn={() => {}} />
    </>
  );
}
