import { Head, router } from '@inertiajs/react';
import { RegraTable } from './components/regras-table';
import {
  index,
  destroy,
} from '@/actions/App/Http/Controllers/RegraAvaliacaoController';
import { useDialog } from '@/hooks/use-dialog';

export default function Index({ regrasAvaliacao }) {
  const { deleteConfirm } = useDialog();

  const handleDelete = (regraId) => {
    deleteConfirm({
      title: 'Tens a certeza?',
      description:
        'Esta acção é irreversível. A regra será eliminada permanentemente.',
      confirmLabel: 'Eliminar',
      confirmFn: () => router.delete(destroy(regraId).url),
    });
  };

  const handlePageChange = (page) => {
    router.visit(index().url, {
      data: { page },
      preserveScroll: true,
    });
  };

  return (
    <>
      <Head title="Regras de Avaliação" />

      <RegraTable
        regras={regrasAvaliacao}
        deleteFn={handleDelete}
        pagination={regrasAvaliacao}
        onPageChange={handlePageChange}
      />
    </>
  );
}
