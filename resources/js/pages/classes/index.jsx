import { router } from '@inertiajs/react';
import { ClasseTable } from './components/classe-table';

export default function Index({ classes }) {
  const excluir = (id) => {
    if (confirm('Tem certeza que deseja excluir essa classe?')) {
      router.delete(`/classes/${id}`);
    }
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <ClasseTable classes={classes} deleteFn={excluir} />
    </div>
  );
}
