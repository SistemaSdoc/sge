import { Head, Link } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';
import { dashboard } from '@/routes';

export default function Bloqueio({ pendencias, total, meses }) {
  return (
    <>
      <Head title="Acesso Bloqueado" />
      <div className="flex flex-col items-center justify-center gap-3 px-4 py-16 text-center sm:gap-4 sm:p-6 sm:py-24">
        <AlertTriangle className="size-8 text-destructive sm:size-10" />
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
                className="flex items-center justify-between gap-4 px-4 py-3 text-sm"
              >
                <span className="font-medium">{p.nome}</span>
                <span className="text-muted-foreground">
                  {meses[p.mes] ?? p.mes} / {p.ano}
                </span>
              </li>
            ))}
          </ul>
        )}

        <p className="text-sm text-muted-foreground">
          Total de{' '}
          <span className="font-semibold text-foreground">{total}</span>{' '}
          {total === 1 ? 'mês em falta' : 'meses em falta'}
        </p>

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
