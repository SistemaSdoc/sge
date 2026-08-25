import { DashboardSummary as Base } from '@/pages/tenant/dashboards/director/components/dashboard-summary';
import { ACCOES } from '@/utils/central-static';

export function DashboardSummary() {
  return <Base items={ACCOES} />;
}