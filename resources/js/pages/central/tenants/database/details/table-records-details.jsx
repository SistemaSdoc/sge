import { DatabaseRecordsChart } from './components/charts/database/database-records-chart';
import { Header } from './components/header';

export default function TableRegistersDetails({ tenant, metrics = {} }) {
  return (
    <div className="mx-auto w-full max-w-6xl space-y-4 p-6">
      {/* Info do tenant */}
      <Header tenant={tenant} title="Tabelas por registos" />

      {/* Métricas */}
      <DatabaseRecordsChart tenant={tenant} registos={metrics} />
    </div>
  );
}
