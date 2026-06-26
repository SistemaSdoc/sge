export function ResumoFinal({ resumo }) {
  if (!resumo) {
    return null;
  }

  const items = [
    { label: 'Total', value: resumo.total, className: '' },
    { label: 'Transita', value: resumo.transita, className: 'text-green-600' },
    {
      label: 'c/ Deficiência',
      value: resumo.transita_com_deficiencia,
      className: 'text-amber-600',
    },
    { label: 'Recurso', value: resumo.recurso, className: 'text-blue-600' },
    {
      label: 'N/Apto',
      value: resumo.nao_transita,
      className: 'text-destructive',
    },
    { label: 'EEF', value: resumo.EEF, className: 'text-orange-600' },
  ];

  return (
    <div className="flex flex-wrap gap-4 text-sm">
      {items.map(({ label, value, className }) => (
        <span key={label} className="text-muted-foreground">
          {label}:{' '}
          <span className={`font-semibold ${className}`}>{value ?? 0}</span>
        </span>
      ))}
    </div>
  );
}
