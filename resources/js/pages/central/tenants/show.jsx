import { DatabaseMetrics } from './components/database-metrics';
import { Header } from './components/tenant-header';
import { UsersMetrics } from './components/users-metrics';

export default function Show({ tenant, metrics = {} }) {
  return (
    <div className="mx-auto w-full max-w-6xl space-y-4 p-6">
      {/* Info do tenant */}
      <Header tenant={tenant} />

      {/* Métricas */}
      <UsersMetrics data={metrics} />

      <DatabaseMetrics tenant={tenant} metrics={metrics.database} />
    </div>
  );
}
