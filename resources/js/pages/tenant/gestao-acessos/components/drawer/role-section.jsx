// components/access-management/SecaoPapeis.jsx
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';

const ROLE_COLORS = [
  'text-orange-600 border-orange-200 bg-orange-50',
  'text-sky-600 border-sky-200 bg-sky-50',
  'text-emerald-600 border-emerald-200 bg-emerald-50',
  'text-indigo-600 border-indigo-200 bg-indigo-50',
  'text-rose-600 border-rose-200 bg-rose-50',
  'text-slate-600 border-slate-200 bg-slate-50',
];

export function RolesSection({ roles, selectedRoles, changeRole }) {
  return (
    <div>
      <div className="mb-3 border-l-2 border-orange-500 pl-2">
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
              id={`papel-${role}`}
              checked={selectedRoles.includes(role)}
              onCheckedChange={(checked) => changeRole(role, checked)}
            />
          </div>
        ))}
      </div>
    </div>
  );
}
