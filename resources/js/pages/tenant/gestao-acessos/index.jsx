import { Head, router } from '@inertiajs/react';
import { UsuariosTable } from './components/usuarios-table';
import { index } from '@/actions/App/Http/Controllers/Tenant/AccessManagementController';
import { useDrawer } from '@/hooks/use-drawer';
import { EditarAcessoDrawer } from './components/drawer';

export default function Index({ users, roles, allPermissions }) {
  const { openForm, closeDrawer } = useDrawer();

  const editarAcessoFn = (usuario) =>
    openForm({
      className: 'p-0',
      content: (
        <EditarAcessoDrawer
          usuario={usuario}
          allRoles={roles}
          allPermissions={allPermissions}
          closeDrawer={closeDrawer}
        />
      ),
    });

  const handlePageChange = (page) => {
    router.visit(index().url, {
      data: { page },
      preserveScroll: true,
    });
  };

  return (
    <>
      <Head title="Gerir Acessos" />

      <UsuariosTable
        usuarios={users}
        pagination={{
          current_page: users?.current_page,
          last_page: users?.last_page,
        }}
        onPageChange={handlePageChange}
        editarAcessoFn={editarAcessoFn}
      />
    </>
  );
}
