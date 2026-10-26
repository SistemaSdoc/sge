export function ReadOnlyField({ label, value }) {
  return (
    <div className="space-y-1">
      <p className="text-sm text-muted-foreground">{label}</p>

      <p className="font-medium">{value || 'Não definido'}</p>
    </div>
  );
}
