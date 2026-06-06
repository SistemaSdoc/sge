import { router } from '@inertiajs/react';
import { ProfessorTable } from './components/professor-table';

export default function Index({ professores }) {
  const excluir = (id) => {
    if (confirm('Tem certeza que deseja excluir esse professor?')) {
      router.delete(`/professores/${id}`);
    }
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <ProfessorTable professores={professores} deleteFn={excluir} />
    </div>
  );
}
