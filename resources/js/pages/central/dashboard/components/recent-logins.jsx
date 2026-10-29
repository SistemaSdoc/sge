import { EmptyState } from '../../../../components/empty-state';

export function RecentLogins({ items = [] }) {
  if (!items.length) {
    return <EmptyState text="Ainda sem registo de logins" />;
  }

  return (
    <div className="overflow-x-auto">
      <table className="w-full min-w-[460px] text-sm">
        <thead>
          <tr className="text-left text-xs text-muted-foreground">
            <th className="py-2 font-medium">Utilizador</th>
            <th className="py-2 font-medium">Instituição</th>
            <th className="py-2 font-medium">Data e hora</th>
            <th className="py-2 font-medium">Estado</th>
          </tr>
        </thead>
        <tbody>
          {items.map((item, index) => (
            <tr key={index} className="border-t">
              <td className="py-3 font-semibold">{item.utilizador}</td>
              <td className="py-3">{item.instituicao}</td>
              <td className="py-3">{item.data}</td>
              <td className="py-3">
                <span
                  className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                    item.sucesso ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'
                  }`}
                >
                  {item.sucesso ? 'Sucesso' : 'Falhou'}
                </span>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}