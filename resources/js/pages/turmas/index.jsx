import { TurmaTable } from './components/turma-table';
import { Head, router } from '@inertiajs/react';
import { index } from '@/actions/App/Http/Controllers/TurmaController';

export default function Index({ turmas }) {
  const handlePageChange = (page) => {
    router.visit(index().url, {
      data: { page },
      preserveScroll: true,
    });
  };
  return (
    <div className='mx-auto w-full max-w-7xl p-6'>
      <Head title="Turmas" />
      <TurmaTable turmas={turmas.data ?? []}
        pagination={{
          current_page: turmas.current_page,
          last_page: turmas.last_page,
        }}
        onPageChange={handlePageChange}
      />
    </div>
  );
}
