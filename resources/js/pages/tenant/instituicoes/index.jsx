import { Head, router } from '@inertiajs/react';
import { InstituicaoTable } from './components/instituicao-table';
import { useDialog } from '@/hooks/use-dialog';
import {
  index,
  destroy,
} from '@/actions/App/Http/Controllers/Tenant/InstituicaoController';

export default function Index({ instituicoes, can, filters }) {
  const { deleteConfirm } = useDialog();

  const handleDelete = (instituicaoId) => {
    deleteConfirm({
      title: 'Tens a certeza?',
      description:
        'Esta acção é irreversível. A instituição será eliminada permanentemente.',
      confirmLabel: 'Eliminar',
      confirmFn: () => router.delete(destroy(instituicaoId).url),
    });
  };

  const handlePageChange = (page) => {
    router.visit(index().url, {
      data: {
        ...Object.fromEntries(new URLSearchParams(window.location.search)),
        page,
      },
      preserveScroll: true,
    });
  };

  return (
    <>
      <Head title="Instituições" />
      <InstituicaoTable
        can={can}
        instituicoes={instituicoes.data}
        filters={filters}
        deleteFn={handleDelete}
        pagination={{
          current_page: instituicoes.current_page,
          last_page: instituicoes.last_page,
        }}
        onPageChange={handlePageChange}
      />
    </>
  );
}
