import { Head, router } from '@inertiajs/react';
import {
  destroy,
  index,
} from '@/actions/App/Http/Controllers/Tenant/UserController';
import { useDialog } from '@/hooks/use-dialog';
import { UserTable } from './components/user-table';

export default function Index({ users, roles, allPermissions }) {
  const { deleteConfirm } = useDialog();

  const handleDelete = (user) => {
    deleteConfirm({
      title: 'Remover usuário?',
      description: `Esta acção removerá o acesso de ${user.nome}.`,
      confirmLabel: 'Remover',
      confirmFn: () => router.delete(destroy(user.id).url),
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
      <Head title="Usuários" />

      <div className="mx-auto w-full max-w-7xl p-6">
        <UserTable
          users={users}
          pagination={users}
          onPageChange={handlePageChange}
          deleteFn={handleDelete}
        />
      </div>
    </>
  );
}
