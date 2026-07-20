import { NotasTable } from './components/notas-table';
import { NotasResumo } from './components/notas-resumo';

export default function Index({ notas }) {
  return (
    <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-6">
      <div className="flex flex-col gap-1">
        <h1 className="text-2xl font-medium tracking-tight">Minhas Notas</h1>
        <p className="text-muted-foreground">
          Consulte seu desempenho em todas as disciplinas
        </p>
      </div>

      <NotasResumo data={notas ?? []} />
      <NotasTable data={notas ?? []} />
    </div>
  );
}
