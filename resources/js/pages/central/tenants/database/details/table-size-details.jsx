import { DatabaseSizeChart } from './components/charts/database/database-size-chart';
import { Header } from './components/header';

export default function TableSizeDetails({ tenant, metrics = {} }) {
  return (
    <div className="mx-auto w-full max-w-6xl space-y-4 p-6">
      {/* Info do tenant */}
      <Header tenant={tenant} title="Tabelas por tamanho" />

      {/* Métricas */}
      <DatabaseSizeChart tenant={tenant} tabelas={metrics} />
    </div>
  );
}
