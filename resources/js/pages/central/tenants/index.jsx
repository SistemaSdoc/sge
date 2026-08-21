import { Head, router } from '@inertiajs/react';
import { TenantTable } from './components/tenant-table';
import {
  index,
  destroy,
} from '@/actions/App/Http/Controllers/Central/TenantController';
import { useDialog } from '@/hooks/use-dialog';
import { AlterarStatusDialog } from './components/alterar-status-dialog';

export default function Index({ tenants, can }) {
  const { deleteConfirm, openForm, closeDialog } = useDialog();

  const handleToggleStatus = (tenant, e) => {
    e.stopPropagation();

    openForm({
      title: `Alterar Status - ${tenant.instituicao?.nome || tenant.id}`,
      description:
        'Selecione uma opcão abaixo para alterar o status do cliente.',
      size: 'md',
      content: (
        <AlterarStatusDialog
          tenant={tenant}
          status={tenant.status}
          onCancel={() => closeDialog()}
          onSuccess={() => closeDialog()}
        />
      ),
    });
  };

  const handleDelete = (tenantId) => {
    deleteConfirm({
      title: 'Tens a certeza?',
      description:
        'Esta acção é irreversível. O tenant será eliminado permanentemente.',
      confirmLabel: 'Eliminar',
      confirmFn: () => router.delete(destroy(tenantId).url),
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
      <Head title="Clientes" />

      <TenantTable
        can={can}
        tenants={tenants.data ?? []}
        deleteFn={handleDelete}
        pagination={tenants.meta}
        onPageChange={handlePageChange}
        handleToggleStatus={handleToggleStatus}
      />
    </>
  );
}
