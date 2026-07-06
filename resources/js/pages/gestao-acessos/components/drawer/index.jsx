import { Button } from '@/components/ui/button';
import { RolesSection } from './role-section';
import { PermissionSection } from './permission-section';
import { HeaderDrawer } from './header';
import { useUserAccessForm } from '@/hooks/use-user-access-form';

export function EditarAcessoDrawer({
  usuario,
  allRoles,
  allPermissions,
  closeDrawer,
}) {
  const {
    roles,
    directPermissions,
    processing,
    changeRole,
    removePermission,
    addPermissions,
    submit,
  } = useUserAccessForm(usuario, closeDrawer);

  const permissoesDisponiveis = (allPermissions ?? []).filter(
    (p) =>
      !directPermissions.includes(p) &&
      !usuario.inheritedPermissions?.includes(p),
  );

  return (
    <form onSubmit={submit} className="flex h-full flex-col">
      <HeaderDrawer usuario={usuario} />

      <div className="flex-1 space-y-6 overflow-y-auto px-4 py-4">
        <RolesSection
          roles={allRoles}
          selectedRoles={roles}
          changeRole={changeRole}
        />
        <PermissionSection
          inheritedPermissions={usuario?.inheritedPermissions}
          directPermissions={directPermissions}
          availablePermissions={permissoesDisponiveis}
          removePermission={removePermission}
          addPermissions={addPermissions}
        />
      </div>

      <div className="border-t px-4 py-3">
        <Button type="submit" className="w-full" disabled={processing}>
          {processing ? 'A Salvar...' : 'Salvar'}
        </Button>
      </div>
    </form>
  );
}
