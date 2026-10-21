import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import {
  Plus,
  BookOpen,
  GraduationCap,
  Clock,
  Eye,
  Calendar,
  Search,
  FileText,
  Layers,
  TrendingUp,
  ArrowLeft,
} from 'lucide-react';
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
        {/* Header */}
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
                <Link href="/dashboard/planificacoes">
                  <ArrowLeft className="mr-1.5 size-4" />
                  Voltar
                </Link>
              </Button>
              {can.create && (
                <Button asChild size="sm" className="hover:cursor-pointer">
                  <Link href={planificacoesCreate().url}>
                    <Plus className="mr-1.5 size-4" />
                    Nova planificação
                  </Link>
                </Button>
              )}
            </CardAction>
          </CardHeader>

          {/* Stats */}
          {planificacoes.length > 0 && (
            <CardContent className="grid grid-cols-1 gap-3 p-4 sm:grid-cols-3">
              <div className="flex items-center gap-3">
                <div className="flex size-10 shrink-0 items-center justify-center bg-muted">
                  <FileText className="size-5 text-muted-foreground" />
                </div>
                <div>
                  <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                    Planificações
                  </p>
                  <p className="text-xl font-bold">{stats.total}</p>
                </div>
              </div>

              <div className="flex items-center gap-3">
                <div className="flex size-10 shrink-0 items-center justify-center bg-muted">
                  <Layers className="size-5 text-muted-foreground" />
                </div>
                <div>
                  <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                    Classes
                  </p>
                  <p className="text-xl font-bold">{stats.classes}</p>
                </div>
              </div>

              {stats.ultima && (
                <div className="flex items-center gap-3">
                  <div className="flex size-10 shrink-0 items-center justify-center bg-muted">
                    <TrendingUp className="size-5 text-muted-foreground" />
                  </div>
                  <div className="min-w-0">
                    <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                      Última actualização
                    </p>
                    <p className="truncate text-sm font-semibold">
                      {stats.ultima.atualizada_em}
                    </p>
                  </div>
                </div>
              )}
            </CardContent>
          )}
        </Card>

        {/* Busca */}
        {planificacoes.length > 0 && (
          <Card className="gap-0 pb-0">
            <CardContent className="p-4">
              <div className="relative">
                <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                  value={busca}
                  onChange={(e) => setBusca(e.target.value)}
                  placeholder="Buscar por título, período, classe ou ano lectivo…"
                  className="pl-9"
                />
              </div>
            </CardContent>
          </Card>
        )}

        {/* Lista */}
        {planificacoes.length === 0 ? (
          <EmptyState
            variant="table"
            icon={BookOpen}
            title="Sem planificações"
            description="Esta disciplina ainda não tem planificações publicadas."
          />
        ) : grupos.length === 0 ? (
          <EmptyState
            variant="table"
            icon={Search}
            title="Sem resultados"
            description={`Nenhuma planificação corresponde a "${busca}".`}
          />
        ) : (
          <div className="space-y-6">
            {grupos.map(({ classe, items }) => (
              <section key={classe.id} className="space-y-3">
                {/* Header da classe */}
                <div className="flex items-center gap-3 border-b pb-2">
                  <div className="flex size-9 shrink-0 items-center justify-center bg-primary/10">
                    <GraduationCap className="size-4 text-primary" />
                  </div>
                  <div className="flex-1">
                    <h2 className="text-base font-semibold leading-none">
                      {classe.nome}
                    </h2>
                    <p className="mt-1 text-xs text-muted-foreground">
                      {items.length}{' '}
                      {items.length === 1 ? 'planificação' : 'planificações'}
                    </p>
                  </div>
                  <Badge variant="secondary" className="font-mono">
                    {items.length}
                  </Badge>
                </div>

                {/* Grid */}
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

                        <CardDescription className="flex items-center gap-1.5 text-xs">
                          <Clock className="size-3 shrink-0" />
                          <span className="truncate">{p.atualizada_em}</span>
                        </CardDescription>
                      </CardHeader>

                      <CardContent className="flex-1 pb-3">
                        <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                          {p.ano_letivo && (
                            <span className="inline-flex items-center gap-1 bg-muted/60 px-2 py-1">
                              <Calendar className="size-3" />
                              {p.ano_letivo}
                            </span>
                          )}
                          {p.classe?.nome && (
                            <span className="inline-flex items-center gap-1 bg-muted/60 px-2 py-1">
                              <GraduationCap className="size-3" />
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
                            <Eye className="mr-1.5 size-4" />
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