// components/access-management/SecaoPermissoes.jsx
import { Lock, X } from 'lucide-react';
import MultiSelectorField from '@/components/multiple-select';

export function PermissionSection({
  inheritedPermissions,
  directPermissions,
  availablePermissions,
  addPermissions,
}) {
  // itens disponíveis = extras já seleccionados + os disponíveis para adicionar
  const items = [
    ...directPermissions.map((p) => ({ value: p, label: p })),
    ...availablePermissions.map((p) => ({ value: p, label: p })),
  ];

  return (
    <div>
      <div className="mb-3 border-l-2 border-orange-500 pl-2">
        <span className="text-xs font-semibold">Permissões</span>
      </div>

      <div className="mb-4 space-y-2">
        <span className="text-[10px] font-semibold text-muted-foreground">
          Herdadas
        </span>
        <div className="mt-1.5 space-y-1.5">
          {inheritedPermissions?.length > 0 ? (
            inheritedPermissions?.map((p) => (
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

      {/* Extras — geridas pelo multiselect */}
      <div className="space-y-4">
        <span className="text-[10px] font-semibold text-muted-foreground">
          Extras
        </span>
        
        <MultiSelectorField
          items={items}
          value={directPermissions.map((p) => ({ value: p, label: p }))}
          onChange={(selecionadas) =>
            addPermissions(selecionadas.map((s) => s.value))
          }
          placeholder="Selecionar permissão..."
        />
      </div>
    </div>
  );
}
