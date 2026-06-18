export function TurmaHeader({ data }) {
  return (
    <div className="bg-muted rounded-lg p-8">
      <h1 className="text-2xl font-bold">{data?.nome}</h1>
      <p className="text-muted-foreground">
        {data?.classe?.nome} — {data?.turno?.nome}
      </p>
    </div>
  );
}