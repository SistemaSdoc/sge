import { router, usePage } from '@inertiajs/react';
import { update } from '@/routes/inscricoes';
import { InscricaoTable } from './components/inscricao-table';

export default function Index() {
  const { inscricoes } = usePage().props;

  const handlePageChange = (page) => {
    router.visit('/inscricoes', {
      data: { page },
      preserveScroll: true,
    
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <InscricaoTable
        inscricoes={inscricoes.data}
        pagination={{
          current_page: inscricoes.current_page,
          last_page: inscricoes.last_page,
        }}
        onPageChange={handlePageChange}
        updateFn={(id, nota_teste) =>
          router.patch(update.url(id), { nota_teste })
        }
      />
    </div>
  );
}
