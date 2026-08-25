import { DatabaseRecordsChart } from './charts/database/database-records-chart';
import { DatabaseSizeChart } from './charts/database/database-size-chart';

export function DatabaseMetrics({ tenant, metrics = {} }) {
  const tabelasTamanho = metrics?.tabelasPorTamanho ?? [];
  const tabelasRegistos = metrics?.tabelasPorRegistos ?? [];

  return (
    <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
      <DatabaseSizeChart tenant={tenant} tabelas={tabelasTamanho} />
      <DatabaseRecordsChart tenant={tenant} tabelas={tabelasRegistos} />
    </div>
  );
}
