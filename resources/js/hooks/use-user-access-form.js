import { useForm } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/Tenant/AccessManagementController';

export function useUserAccessForm(usuario, onSuccess) {
  const { data, setData, post, processing } = useForm({
    roles: usuario?.roles ?? [],
    directPermissions: usuario?.directPermissions ?? [],
  });

  function changeRole(role, checked) {
    setData(
      'roles',
      checked ? [...data.roles, role] : data.roles.filter((r) => r !== role),
    );
  }

  function removePermission(permission) {
    setData(
      'directPermissions',
      data.directPermissions.filter((p) => p !== permission),
    );
  }

  function addPermissions(novas) {
    setData('directPermissions', novas);
  }

  const submit = (e) => {
    e.preventDefault();
    post(store.url(usuario.id), {
      preserveScroll: true,
      onSuccess: () => onSuccess?.(), // ← chama o closeDrawer se foi passado
    });
  };

  return {
    roles: data.roles,
    directPermissions: data.directPermissions,
    processing,
    changeRole,
    removePermission,
    addPermissions,
    submit,
  };
}
