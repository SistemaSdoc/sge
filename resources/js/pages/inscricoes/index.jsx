import { router, usePage } from '@inertiajs/react';
import { update } from '@/routes/inscricoes';
import { InscricaoTable } from './components/inscricao-table';

export default function Index() {
  const { inscricoes } = usePage().props;

  return (
    <InscricaoTable
      inscricoes={inscricoes}
      updateFn={(id, nota_teste) =>
        router.patch(update.url(id), { nota_teste })
      }
    />
  );
}