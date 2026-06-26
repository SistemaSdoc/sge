import { router, usePage } from '@inertiajs/react';
import { AlunoHeader } from './components/show/aluno-header';
import { AlunoDetails } from './components/show/aluno-detalhes';
import { AlunoRelated } from './components/show/aluno-related';

export default function Show() {
  const { aluno } = usePage().props;

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6 p-6">
      <AlunoHeader aluno={aluno} />
      <AlunoDetails aluno={aluno} />
      <AlunoRelated aluno={aluno} />
    </div>
  );
}
