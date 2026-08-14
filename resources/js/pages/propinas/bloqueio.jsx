import { Head, Link } from '@inertiajs/react';
import { dashboard } from '@/routes';

const formatCurrency = (value) => {
  const amount = Number(value ?? 0);
  return `${amount.toLocaleString('pt', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} AOA`;
};

export default function Bloqueio({ pendencias, total, meses }) {
  const valorTotal = pendencias.reduce((soma, p) => soma + Number(p.valor ?? 0), 0);
  const multaTotal = pendencias.reduce((soma, p) => soma + Number(p.multa ?? 0), 0);

  return (
    <>
      <Head title="Acesso Bloqueado" />
      <div className="flex flex-col items-center justify-center gap-3 px-4 py-16 text-center sm:gap-4 sm:p-6 sm:py-24">
      
        <h1 className="text-lg font-semibold sm:text-xl">Propinas em atraso</h1>

        <p className="max-w-xs text-sm text-muted-foreground sm:max-w-md">
          <strong className="text-foreground">Acesso bloqueado</strong> — não é
          possível continuar enquanto houver pendências.
        </p>

        {pendencias.length > 0 && (
          <ul className="w-full max-w-xs divide-y divide-border rounded-md border text-left sm:max-w-md">
            {pendencias.map((p, i) => (
              <li
                key={i}
                className="flex flex-col gap-1 px-4 py-3 text-sm"
              >
                <div className="flex items-center justify-between gap-4">
                  <span className="font-medium">{p.nome}</span>
                  <span className="text-muted-foreground">
                    {meses[p.mes] ?? p.mes} / {p.ano}
                  </span>
                </div>

                <div className="flex items-center justify-between gap-4 text-xs text-muted-foreground">
                  <span>Propina: {formatCurrency(p.valor_base ?? p.valor)}</span>
                  {p.multa > 0 && (
                    <span className="flex items-center gap-1 font-medium  ">
                      
                      Multa: {formatCurrency(p.multa)}
                    </span>
                  )}
                </div>
              </li>
            ))}
          </ul>
        )}

        <div className="flex flex-col items-center gap-1">
          <p className="text-sm text-muted-foreground">
            Total de{' '}
            <span className="font-semibold text-foreground">{total}</span>{' '}
            {total === 1 ? 'mês em falta' : 'meses em falta'}
          </p>

          {multaTotal > 0 && (
            <p className="text-xs  ">
              Inclui {formatCurrency(multaTotal)} em multas por atraso
            </p>
          )}

          <p className="text-sm font-semibold text-foreground">
            Total a pagar: {formatCurrency(valorTotal)}
          </p>
        </div>

        <Link
          href={dashboard()}
          className="text-sm font-medium text-primary underline underline-offset-4"
        >
          Voltar ao Dashboard
        </Link>
      </div>
    </>
  );
}