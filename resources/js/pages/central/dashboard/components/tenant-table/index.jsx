import { router } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { estadoBadge } from '@/utils/central-static';

export function TenantTable({ tenants = [] }) {
  if (!tenants.length) {
    return (
      <p className="py-6 text-center text-xs text-muted-foreground">
        Nenhuma instituição registada.
      </p>
    );
  }

  return (
    <div className="overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b text-left text-xs text-muted-foreground">
            <th className="pb-2 font-medium">Instituição</th>
            <th className="pb-2 font-medium">Tipo</th>
            <th className="pb-2 font-medium">Estado</th>
            <th className="pb-2 font-medium">Criado em</th>
            <th className="pb-2" />
          </tr>
        </thead>
        <tbody className="divide-y divide-border">
          {tenants.map((t) => (
            <tr
              key={t.id}
              className="group cursor-pointer transition-colors hover:bg-muted/40"
              onClick={() => router.visit(`/dashboard/tenants/${t.id}`)}
            >
              <td className="py-3 font-medium">{t.nome}</td>
              <td className="py-3 text-muted-foreground capitalize">
                {t.tipo}
              </td>
              <td className="py-3">
                <span
                  className={`rounded-full px-2 py-0.5 text-xs font-medium ${estadoBadge[t.estado] ?? ''}`}
                >
                  {t.estadoLabel}
                </span>
              </td>
              <td className="py-3 text-muted-foreground">{t.criadoEm}</td>
              <td className="py-3 text-right">
                <ChevronRight className="ml-auto size-4 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100" />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
