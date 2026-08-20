import { useForm } from '@inertiajs/react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { useInitials } from '@/hooks/use-initials';
import { Lock, X } from 'lucide-react';
import { store } from '@/actions/App/Http/Controllers/Tenant/AccessManagementController';
import MultiSelectorField from '@/components/multiple-select';

const ROLE_COLORS = [
  'text-orange-600 border-orange-200 bg-orange-50',
  'text-sky-600 border-sky-200 bg-sky-50',
  'text-emerald-600 border-emerald-200 bg-emerald-50',
  'text-indigo-600 border-indigo-200 bg-indigo-50',
  'text-rose-600 border-rose-200 bg-rose-50',
  'text-slate-600 border-slate-200 bg-slate-50',
];

export function EditarAcessoDrawer({ usuario, roles, allPermissions }) {
  const getInitials = useInitials();

  const { data, setData, post, processing } = useForm({
    roles: usuario.roles ?? [],
    directPermissions: usuario.directPermissions ?? [],
  });

  function handleSubmit(e) {
    e.preventDefault();
    post(store.url(usuario.id), { preserveScroll: true });
  }

  function toggleRole(role, checked) {
    setData(
      'roles',
      checked ? [...data.roles, role] : data.roles.filter((r) => r !== role),
    );
  }

  function removerPermissao(p) {
    setData('directPermissions', data.directPermissions.filter((d) => d !== p));
  }

  // Permissões disponíveis para adicionar — exclui as já directas e herdadas
  const permissoesDisponiveis = (allPermissions ?? []).filter(
    (p) =>
      !data.directPermissions.includes(p) &&
      !usuario.inheritedPermissions?.includes(p),
  );

  return (
    <form onSubmit={handleSubmit} className="flex h-full flex-col">

      {/* Header fixo */}
      <div className="flex items-center gap-3 border-b px-4 pb-3">
        <Avatar>
          <AvatarImage src={usuario.avatar} alt={usuario.nome} className="grayscale" />
          <AvatarFallback>{getInitials(usuario.nome)}</AvatarFallback>
        </Avatar>
        <div className="flex flex-col">
          <span className="text-sm font-medium">{usuario.nome}</span>
          <span className="text-[10px] text-muted-foreground">{usuario.email}</span>
        </div>
      </div>

      {/* Conteúdo com scroll */}
      <div className="flex-1 space-y-6 overflow-y-auto px-4 py-4">

        {/* Papéis */}
        <div>
          <div className="mb-2 border-l-2 border-orange-500 pl-2">
            <span className="text-xs font-semibold tracking-wide">Papéis</span>
          </div>
          <div className="space-y-2">
            {roles?.map((role, i) => (
              <div key={role} className="flex items-center justify-between">
                <Badge
                  variant="outline"
                  className={`text-[10px] font-medium ${ROLE_COLORS[i % ROLE_COLORS.length]}`}
                >
                  {role}
                </Badge>

                <Switch
                  id={`role-${role}`}
                  checked={data.roles.includes(role)}
                  onCheckedChange={(checked) => toggleRole(role, checked)}
                />
              </div>
            ))}
          </div>
        </div>

        {/* Permissões */}
        <div>
          <div className="mb-2 border-l-2 border-orange-500 pl-2">
            <span className="text-xs font-semibold">Permissões</span>
          </div>

          {/* Herdadas */}
          <div className="mb-4 space-y-2">
            <span className="text-[10px] font-semibold text-muted-foreground">
              Herdadas
            </span>
            <div className="mt-1.5 space-y-1.5">
              {usuario.inheritedPermissions?.length > 0 ? (
                usuario.inheritedPermissions.map((p) => (
                  <div
                    key={p}
                    className="flex items-center gap-2 bg-muted/50 px-3 py-1.5 text-xs text-muted-foreground"
                  >
                    <Lock size={11} />
                    {p}
                  </div>
                ))
              ) : (
                <span className="text-xs text-muted-foreground">Nenhuma</span>
              )}
            </div>
          </div>

          {/* Extras */}
          <div className="space-y-2">
            <span className="text-[10px] font-semibold tracking-wider text-muted-foreground">
              Extras
            </span>
            <div className="mt-1.5 space-y-1.5">
              {data.directPermissions.map((p) => (
                <div
                  key={p}
                  className="flex items-center justify-between rounded-md border px-3 py-1.5 text-xs"
                >
                  <span>{p}</span>
                  <button
                    type="button"
                    onClick={() => removerPermissao(p)}
                    className="text-muted-foreground hover:text-destructive"
                  >
                    <X size={13} />
                  </button>
                </div>
              ))}
              {data.directPermissions.length === 0 && (
                <span className="text-xs text-muted-foreground">Nenhuma</span>
              )}
            </div>

            {/* Selector para adicionar permissões */}
            <MultiSelectorField
              items={permissoesDisponiveis.map((p) => ({ value: p, label: p }))}
              value={[]}
              onChange={(selected) =>
                setData('directPermissions', [
                  ...data.directPermissions,
                  ...selected.map((s) => s.value),
                ])
              }
              placeholder="Selecionar permissão..."
            />
          </div>
        </div>

      </div>

      {/* Footer fixo */}
      <div className="border-t px-4 py-3">
        <Button type="submit" className="w-full" disabled={processing}>
          {processing ? 'A guardar...' : 'Guardar alterações'}
        </Button>
      </div>

    </form>
  );
}