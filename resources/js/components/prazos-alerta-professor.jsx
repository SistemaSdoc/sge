import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export function PrazosAlertaProfessor({ prazos = [] }) {
  if (!Array.isArray(prazos) || prazos.length === 0) return null;

  const totalPendentes = prazos.reduce(
    (acc, p) => acc + (p.tudo_submetido ? 0 : 1),
    0,
  );
  const totalUrgentes = prazos.filter(
    (p) => p.urgente && !p.tudo_submetido && !p.expirado,
  ).length;
  const totalExpirados = prazos.filter(
    (p) => p.expirado && !p.tudo_submetido,
  ).length;

  const tudoSubmetido = totalPendentes === 0;

  return (
    <div className="flex flex-col gap-3  border bg-card p-4 text-card-foreground shadow-sm sm:flex-row sm:items-center sm:justify-between">
      {/* Lado esquerdo: contador + texto */}
      <div className="flex min-w-0 items-center gap-3">
        <div
          className={
            'flex size-10 shrink-0 items-center justify-center ' +
            (tudoSubmetido
              ? 'bg-muted text-muted-foreground'
              : totalUrgentes > 0
                ? 'bg-destructive/15 text-destructive'
                : 'bg-muted text-foreground')
          }
        >
          <span className="text-base font-bold tabular-nums">
            {totalPendentes}
          </span>
        </div>

        <div className="min-w-0">
          <p className="text-sm font-semibold leading-tight">
            {tudoSubmetido
              ? 'Todos os prazos foram submetidos'
              : `${totalPendentes} ${totalPendentes === 1 ? 'prazo pendente' : 'prazos pendentes'} de submissão de propostas de provas`}
          </p>
          <div className="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
            {totalUrgentes > 0 && (
              <span className="font-medium text-destructive">
                {totalUrgentes} {totalUrgentes === 1 ? 'urgente' : 'urgentes'}
              </span>
            )}
            {totalExpirados > 0 && (
              <span>
                {totalExpirados}{' '}
                {totalExpirados === 1 ? 'expirado' : 'expirados'}
              </span>
            )}
            {totalUrgentes === 0 && totalExpirados === 0 && (
              <span>{prazos.length} no total</span>
            )}
          </div>
        </div>
      </div>

      {/* Lado direito: botão */}
      <Button
        asChild
        variant="outline"
        size="sm"
        className="shrink-0 hover:cursor-pointer"
      >
        <Link href="/dashboard/professor/provas">Ver página de provas</Link>
      </Button>
    </div>
  );
}