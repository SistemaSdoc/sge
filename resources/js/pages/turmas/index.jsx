import { TurmaTable } from './components/turma-table';
import { Head } from '@inertiajs/react';

export default function Index({ turmas }) {
  return (
    <div className='mx-auto w-full max-w-7xl p-6'>
      <Head title="Turmas" />
      <TurmaTable turmas={turmas.data ?? []} />
    </div>
  );
}
