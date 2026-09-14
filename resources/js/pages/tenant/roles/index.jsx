import { Head, router } from '@inertiajs/react';
import {
  destroy,
  index,
} from '@/actions/App/Http/Controllers/Tenant/RoleController';
import { useDialog } from '@/hooks/use-dialog';
import { RoleTable } from './components/role-table';

export default function Index({ roles }) {
  const { deleteConfirm } = useDialog();

  const handleDelete = (role) => {
    deleteConfirm({
      title: 'Remover função?',
      description: `Esta acção removerá a função ${role.name} e permissões associadas.`,
      confirmLabel: 'Remover',
      confirmFn: () => router.delete(destroy(role.id).url),
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
      <Head title="Funções" />

      <div className="mx-auto w-full max-w-7xl p-6">
        <RoleTable
          roles={roles}
          pagination={roles}
          onPageChange={handlePageChange}
          deleteFn={handleDelete}
        />
      </div>
    </>
  );
}
