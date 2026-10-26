import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { EmptyState } from '@/components/empty-state';
import { PeriodoBadge } from '@/components/periodo-badge';
import { create as planificacoesCreate } from '@/actions/App/Http/Controllers/Tenant/PlanificacaoController';

export default function Disciplina({
  disciplina,
  planificacoes = [],
  can = {},
}) {
  const [busca, setBusca] = useState('');

  const grupos = useMemo(() => {
    const termo = busca.trim().toLowerCase();

    const filtradas = termo
      ? planificacoes.filter(
          (p) =>
            (p.titulo ?? '').toLowerCase().includes(termo) ||
            (p.periodo ?? '').toLowerCase().includes(termo) ||
            (p.classe?.nome ?? '').toLowerCase().includes(termo) ||
            (p.ano_letivo ?? '').toLowerCase().includes(termo),
        )
      : planificacoes;

    const mapa = new Map();

    filtradas.forEach((p) => {
      const key = p.classe?.id ?? 'sem-classe';

      if (!mapa.has(key)) {
        mapa.set(key, {
          classe: p.classe ?? { id: 'sem-classe', nome: 'Sem classe' },
          items: [],
        });
      }

      mapa.get(key).items.push(p);
    });

    const lista = Array.from(mapa.values());
    lista.forEach((g) =>
      g.items.sort((a, b) => (a.periodo ?? '').localeCompare(b.periodo ?? '')),
    );

    return lista.sort((a, b) =>
      (a.classe.nome ?? '').localeCompare(b.classe.nome ?? ''),
    );
  }, [planificacoes, busca]);

  const stats = useMemo(() => {
    const total = planificacoes.length;
    const classes = new Set(
      planificacoes.map((p) => p.classe?.id ?? 'sem-classe'),
    ).size;
    const ultima = [...planificacoes]
      .filter((p) => p.atualizada_em)
      .sort(
        (a, b) =>
          new Date(b.atualizada_em).getTime() -
          new Date(a.atualizada_em).getTime(),
      )[0];

    return { total, classes, ultima };
  }, [planificacoes]);

  return (
    <>
      <Head title={`Planificações — ${disciplina.nome}`} />

      <div className="mx-auto w-full max-w-7xl space-y-4 p-4 sm:p-6">
        {/* Header — só breadcrumb + ações */}
        <Card className="gap-0 pb-0">
          <CardHeader className="flex flex-col gap-3 border-b border-foreground/10 md:flex-row md:items-center">
            <div className="min-w-0 md:flex-1">
              <Breadcrumb>
                <BreadcrumbList className="flex-wrap">
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbLink asChild>
                      <Link
                        href="/dashboard/planificacoes"
                        className="line-clamp-2 text-sm font-semibold text-primary"
                      >
                        Planificações
                      </Link>
                    </BreadcrumbLink>
                  </BreadcrumbItem>
                  <BreadcrumbSeparator />
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbPage className="line-clamp-2 text-sm font-semibold text-secondary">
                      {disciplina.nome}
                    </BreadcrumbPage>
                  </BreadcrumbItem>
                </BreadcrumbList>
              </Breadcrumb>
              <CardDescription>
                {disciplina.sigla && (
                  <span className="font-mono">{disciplina.sigla} · </span>
                )}
                Todas as planificações publicadas para esta disciplina.
              </CardDescription>
            </div>
            <CardAction className="flex flex-wrap items-center gap-2">
              <Button variant="outline" size="sm" asChild>
                <Link href="/dashboard/planificacoes">Voltar</Link>
              </Button>
              {can.create && (
                <Button asChild size="sm" className="hover:cursor-pointer">
                  <Link href={planificacoesCreate().url}>
                    Nova planificação
                  </Link>
                </Button>
              )}
            </CardAction>
          </CardHeader>
        </Card>

        {/* Stats — 3 cards irmãos (fora do Card do header) */}
        {planificacoes.length > 0 && (
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <Card className="gap-0 pb-0">
              <CardContent className="p-4">
                <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                  Planificações
                </p>
                <p className="mt-1 text-xl font-bold">{stats.total}</p>
              </CardContent>
            </Card>

            <Card className="gap-0 pb-0">
              <CardContent className="p-4">
                <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                  Classes
                </p>
                <p className="mt-1 text-xl font-bold">{stats.classes}</p>
              </CardContent>
            </Card>

            {stats.ultima && (
              <Card className="gap-0 pb-0">
                <CardContent className="min-w-0 p-4">
                  <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                    Última atualização
                  </p>
                  <p className="mt-1 truncate text-sm font-semibold">
                    {stats.ultima.atualizada_em}
                  </p>
                </CardContent>
              </Card>
            )}
          </div>
        )}

        {/* Busca */}
        {planificacoes.length > 0 && (
          <Card className="gap-0 pb-0">
            <CardContent className="p-4">
              <Input
                value={busca}
                onChange={(e) => setBusca(e.target.value)}
                placeholder="Buscar por título, período, classe ou ano letivo…"
              />
            </CardContent>
          </Card>
        )}

        {/* Lista */}
        {planificacoes.length === 0 ? (
          <EmptyState
            variant="table"
            title="Sem planificações"
            description="Esta disciplina ainda não tem planificações publicadas."
          />
        ) : grupos.length === 0 ? (
          <EmptyState
            variant="table"
            title="Sem resultados"
            description={`Nenhuma planificação corresponde a "${busca}".`}
          />
        ) : (
          <div className="space-y-6">
            {grupos.map(({ classe, items }) => (
              <section key={classe.id} className="space-y-3">
                <div className="flex items-center gap-3 border-b pb-2">
                  <div className="flex-1">
                    <h2 className="text-base leading-none font-semibold">
                      {classe.nome}
                    </h2>
                    <p className="mt-1 text-xs text-muted-foreground">
                      {items.length}{' '}
                      {items.length === 1 ? 'planificação' : 'planificações'}
                    </p>
                  </div>
                 
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                  {items.map((p) => (
                    <Card
                      key={p.id}
                      className="flex flex-col transition-shadow hover:shadow-md"
                    >
                      <CardHeader className="pb-3">
                        <div className="flex items-start justify-between gap-2">
                          <PeriodoBadge
                            periodo={p.periodo}
                            className="text-[10px]"
                          />
                          <Badge
                            variant="outline"
                            className="font-mono text-[10px]"
                          >
                            v{p.versao}
                          </Badge>
                        </div>

                        <CardTitle className="mt-2 line-clamp-2 text-base leading-snug">
                          {p.titulo}
                        </CardTitle>

                        <CardDescription className="truncate text-xs">
                          {p.atualizada_em}
                        </CardDescription>
                      </CardHeader>

                      <CardContent className="flex-1 pb-3">
                        <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                          {p.ano_letivo && (
                            <span className="inline-flex items-center bg-muted/60 px-2 py-1">
                              {p.ano_letivo}
                            </span>
                          )}
                          {p.classe?.nome && (
                            <span className="inline-flex items-center bg-muted/60 px-2 py-1">
                              {p.classe.nome}
                            </span>
                          )}
                        </div>
                      </CardContent>

                      <div className="border-t p-3">
                        <Button
                          variant="outline"
                          asChild
                          className="w-full hover:cursor-pointer"
                        >
                          <Link href={`/dashboard/planificacoes/${p.id}`}>
                            Ver detalhes
                          </Link>
                        </Button>
                      </div>
                    </Card>
                  ))}
                </div>
              </section>
            ))}
          </div>
        )}
      </div>
    </>
  );
}