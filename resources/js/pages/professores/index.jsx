import { router } from '@inertiajs/react';
import { ProfessorTable } from './components/professor-table';

export default function Index({ professores }) {
  const excluir = (id) => {
    if (confirm('Tem certeza que deseja excluir esse professor?')) {
      router.delete(`/professores/${id}`);
    }
  };

   const handlePageChange = (page) => {
    router.visit('/professores', {
      data: { page },
      preserveScroll: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <ProfessorTable 
      pagination={{
          current_page: professores.current_page,
          last_page: professores.last_page,
        }}
        onPageChange={handlePageChange}
      professores={professores} 
      deleteFn={excluir} />
    </div>
  );
}
