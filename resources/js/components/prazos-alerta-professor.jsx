import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

export function PrazosAlertaProfessor({ prazos = [] }) {
  if (!Array.isArray(prazos) || prazos.length === 0) return null;

  const totalPendentes = prazos.reduce(
    (acc, p) => acc + (p.tudo_submetido ? 0 : 1),
    0,
  );

  return (
    <div className="overflow-hidden rounded-lg border bg-card text-card-foreground shadow-sm">
      {/* Header */}
      <div className="border-b px-4 py-3">
        <div className="flex flex-wrap items-start justify-between gap-2">
          <div className="min-w-0 flex-1">
            <h3 className="text-base font-semibold leading-none">
              Prazos de submissão
            </h3>
            <p className="mt-1 text-xs text-muted-foreground">
              {totalPendentes === 0
                ? 'Todos os prazos em curso foram submetidos.'
                : `${totalPendentes} ${totalPendentes === 1 ? 'prazo pendente' : 'prazos pendentes'} de submissão.`}
            </p>
          </div>
          <Badge variant="outline" className="shrink-0 text-[10px]">
            {prazos.length} {prazos.length === 1 ? 'prazo' : 'prazos'}
          </Badge>
        </div>
      </div>

      {/* Lista */}
      <div className="divide-y">
        {prazos.map((prazo) => (
          <PrazoItem key={prazo.prazo_id} prazo={prazo} />
        ))}
      </div>
    </div>
  );
}

function PrazoItem({ prazo }) {
  const {
    titulo,
    disciplina,
    classe,
    periodo,
    data_limite,
    dias_restantes,
    urgente,
    expirado,
    total_turmas,
    total_submetidas,
    turmas = [],
    justificativa,
    tudo_submetido,
    url,
  } = prazo;

  return (
    <div className="space-y-3 px-4 py-4">
      <div className="flex flex-wrap items-start justify-between gap-2">
        <div className="min-w-0 flex-1">
          <div className="flex flex-wrap items-center gap-2">
            <h4 className="truncate text-sm font-semibold" title={titulo}>
              {titulo}
            </h4>
            {tudo_submetido ? (
              <Badge variant="secondary" className="text-[10px]">
                ✓ Já submetido
              </Badge>
            ) : expirado ? (
              <Badge variant="outline" className="text-[10px]">
                Expirado
              </Badge>
            ) : urgente ? (
              <Badge variant="secondary" className="text-[10px] font-bold">
                Urgente
              </Badge>
            ) : (
              <Badge variant="outline" className="text-[10px]">
                Pendente
              </Badge>
            )}
            {justificativa && !tudo_submetido && (
              <Badge variant="outline" className="text-[10px]">
                {justificativa.status_label}
              </Badge>
            )}
          </div>
          <p className="mt-1 truncate text-xs text-muted-foreground">
            {disciplina}
            {classe && ` • ${classe}`}
            {periodo && ` • ${periodo}`}
          </p>
          <p className="mt-0.5 text-xs">
            <span className="text-muted-foreground">Limite: </span>
            <span className={urgente && !expirado ? 'font-semibold' : ''}>
              {data_limite}
            </span>
            {!expirado && !tudo_submetido && (
              <span className="ml-1 text-muted-foreground">
                (
                {dias_restantes <= 0
                  ? 'expira hoje'
                  : `${dias_restantes} ${dias_restantes === 1 ? 'dia' : 'dias'}`}
                )
              </span>
            )}
          </p>
        </div>

        <div className="shrink-0 text-right">
          <p className="text-[11px] uppercase tracking-wide text-muted-foreground">
            Turmas
          </p>
          <p className="font-mono text-sm tabular-nums">
            {total_submetidas}/{total_turmas}
          </p>
        </div>
      </div>

      {/* Lista de turmas */}
      <div className="overflow-hidden rounded border">
        <div className="divide-y">
          {turmas.map((t) => (
            <div
              key={t.turma_id}
              className="flex flex-wrap items-center justify-between gap-2 px-3 py-2"
            >
              <div className="flex min-w-0 flex-1 items-center gap-2">
                <span className="truncate text-sm font-medium">
                  {t.turma_nome}
                </span>
                {t.submeteu ? (
                  <Badge variant="secondary" className="text-[10px]">
                    ✓ {t.estado_label ?? 'Submetido'}
                  </Badge>
                ) : justificativa && justificativa.status === 'pendente' ? (
                  <Badge variant="outline" className="text-[10px]">
                    Justificação pendente
                  </Badge>
                ) : (
                  <Badge variant="outline" className="text-[10px]">
                    Não submetido
                  </Badge>
                )}
              </div>

              {!t.submeteu && (
                <Button
                  asChild
                  variant="outline"
                  size="sm"
                  className="h-7 text-xs hover:cursor-pointer"
                >
                  <Link href={`${url}?turma_id=${t.turma_id}`}>
                    Submeter
                  </Link>
                </Button>
              )}
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}