import { router } from '@inertiajs/react';
import { GrelhaTable } from './components/grelha-table';

export default function Index({ grelhaCurricular, classes = [], classeId }) {
  const handleClasseChange = (value) => {
    const params = new URLSearchParams(window.location.search);
    params.set('classe_id', value);
    router.get(window.location.pathname, Object.fromEntries(params.entries()), {
      preserveState: true,
      replace: true,
    });
  };

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

      <GrelhaTable
        data={grelhaCurricular ?? []}
        classes={classes}
        classeId={classeId}
        handleClasseChange={handleClasseChange}
      />
    </div>
  );
}
