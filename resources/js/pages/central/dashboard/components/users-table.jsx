import { EmptyState } from './empty-state';

export function UsersTable({ items = [], emptyText = 'Sem dados' }) {
  if (!items.length) {
    return <EmptyState text={emptyText} />;
  }

  const max = Math.max(1, ...items.map((item) => item.total));

  return (
    <table className="w-full">
      <thead>
        <tr className="text-xs text-muted-foreground">
          <th scope="col" className="py-2 text-left font-medium">Instituição</th>
          <th scope="col" className="py-2 pl-4 text-right font-medium">Professores</th>
          <th scope="col" className="py-2 pl-4 text-right font-medium">Alunos</th>
          <th scope="col" className="py-2 pl-4 text-right font-medium">Total</th>
        </tr>
      </thead>
      <tbody>
        {items.map((item) => (
          <tr key={item.label} className="border-t">
            <td className="w-full py-3">
              <div className="mb-1.5 text-sm">{item.label}</div>
              <div className="h-1.5 bg-muted">
                <div
                  className="h-full"
                  style={{ width: `${(item.total / max) * 100}%`, background: item.color ?? 'var(--color-info)' }}
                />
              </div>
            </td>
            <td className="py-3 pl-4 text-right text-sm">{item.professores ?? 0}</td>
            <td className="py-3 pl-4 text-right text-sm">{item.alunos ?? 0}</td>
            <td className="py-3 pl-4 text-right text-sm font-bold">{item.total}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}