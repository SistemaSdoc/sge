export function TurmaDetails({ data }) {
  return (
    <div className="grid grid-cols-3 gap-4">
      <div>
        <p className="text-muted-foreground text-sm">Máximo de alunos</p>
        <p className="font-medium">{data?.max_alunos}</p>
      </div>
      <div>
        <p className="text-muted-foreground text-sm">Total de alunos</p>
        <p className="font-medium">{data?.alunos?.length ?? 0}</p>
      </div>
      <div>
        <p className="text-muted-foreground text-sm">Total de disciplinas</p>
        <p className="font-medium">{data?.disciplinas?.length ?? 0}</p>
      </div>
    </div>
  );
}