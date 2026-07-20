import { GrelhaTable } from './components/grelha-table';

export default function Index({ grelhaCurricular }) {
  return (
    <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-6">
      <div className="flex flex-col gap-1">
        <h1 className="text-2xl font-medium tracking-tight">
          Grelha Curricular
        </h1>
        <p className="text-muted-foreground">
          Acompanhe a grelha curricular da sua turma
        </p>
      </div>

      <GrelhaTable data={grelhaCurricular ?? []} />
    </div>
  );
}
