import {
  Card,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import ExportTenantUsersMetrics from '@/actions/App/Actions/Central/Tenant/ExportTenantUsersMetrics';
import { UsersLoginsChart } from './charts/users/users-logins-chart';
import { UsersStatusChart } from './charts/users/users-status-chart';
import { UsersTypeChart } from './charts/users/users-type-chart';
import { ChartSection } from './chart-section';
import { Download } from 'lucide-react';

export function UsersMetrics({ data, tenantId }) {
  return (
    <Card className="gap-0! overflow-visible pb-0">
      <CardHeader className="border-b border-foreground/10">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <CardTitle>Métricas de usuários</CardTitle>
            <CardDescription>
              Acompanhe as métricas dos usuários desta instituição
            </CardDescription>
          </div>
          <Button asChild variant="outline" size="sm" className="shrink-0">
            <a href={ExportTenantUsersMetrics.url({ tenant: tenantId })}>
              <Download data-icon="inline-start" />
              Exportar métricas
            </a>
          </Button>
        </div>
      </CardHeader>

      <div className="grid grid-cols-1 divide-y divide-foreground/10 lg:grid-cols-3 lg:divide-x lg:divide-y-0">
        <ChartSection
          title="Estado dos usuários"
          description="Activos, inactivos e suspensos"
        >
          <UsersStatusChart users={data?.users} />
        </ChartSection>

        <ChartSection
          title="Tipos de usuários"
          description="Distribuição por perfil"
        >
          <UsersTypeChart users={data?.users} />
        </ChartSection>

        <ChartSection
          title="Logins esta semana"
          description="Actividade dos últimos 7 dias"
        >
          <UsersLoginsChart logins_semana={data?.logins_semana} />
        </ChartSection>
      </div>
    </Card>
  );
}
