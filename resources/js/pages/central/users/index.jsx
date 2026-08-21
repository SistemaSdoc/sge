import { useDialog } from '@/hooks/use-dialog';
import { Head, router } from '@inertiajs/react';
import UserTable from './components/user-table';
import {
  index,
  destroy,
} from '@/actions/App/Http/Controllers/Central/UserController';

export default function Index({ users, can }) {
  const { deleteConfirm } = useDialog();

  const handleDelete = (userId) => {
    deleteConfirm({
      title: 'Tens a certeza?',
      description:
        'Esta acção é irreversível. O utilizador será eliminado permanentemente.',
      confirmLabel: 'Eliminar',
      confirmFn: () => router.delete(destroy(userId).url),
    });
  };

  const handlePageChange = (page) => {
    router.visit(index().url, {
      data: { page },
      preserveScroll: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <Head title="Utilizadores" />
      <UserTable
        can={can}
        users={users.data}
        deleteFn={handleDelete}
        pagination={{
          current_page: users.current_page,
          last_page: users.last_page,
        }}
        onPageChange={handlePageChange}
      />
    </div>
  );
}