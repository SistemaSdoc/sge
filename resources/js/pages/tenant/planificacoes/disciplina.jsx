import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import {
  ArrowLeft,
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
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from '@/components/ui/empty';
import { PeriodoBadge } from '@/components/periodo-badge';
import { create as planificacoesCreate } from '@/actions/App/Http/Controllers/Tenant/PlanificacaoController';

export default function Disciplina({
  disciplina,
  planificacoes = [],
  can = {},
}) {
  const [busca, setBusca] = useState('');

  // Agrupa por classe + filtra pela busca
  const grupos = useMemo(() => {
    const termo = busca.trim().toLowerCase();

    const filtradas = termo
      ? planificacoes.filter(
          (p) =>
            (p.titulo ?? '').toLowerCase().includes(termo) ||
            (p.periodo ?? '').toLowerCase().includes(termo) ||
            (p.classe?.nome ?? '').toLowerCase().includes(termo) ||
            (p.ano_letivo ?? '').toLowerCase().includes(termo)
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
      g.items.sort((a, b) => (a.periodo ?? '').localeCompare(b.periodo ?? ''))
    );

    return lista.sort((a, b) =>
      (a.classe.nome ?? '').localeCompare(b.classe.nome ?? '')
    );
  }, [planificacoes, busca]);

  // Estatísticas
  const stats = useMemo(() => {
    const total = planificacoes.length;
    const classes = new Set(
      planificacoes.map((p) => p.classe?.id ?? 'sem-classe')
    ).size;
    const ultima = [...planificacoes]
      .filter((p) => p.atualizada_em)
      .sort(
        (a, b) =>
          new Date(b.atualizada_em).getTime() -
          new Date(a.atualizada_em).getTime()
      )[0];

    return { total, classes, ultima };
  }, [planificacoes]);

  const temBusca = busca.trim().length > 0;

  return (
    <>
      <Head title={`Planificações — ${disciplina.nome}`} />

      <div className="mx-auto w-full max-w-7xl space-y-6 p-4 sm:p-6">
        {/* Voltar */}
        <Button variant="ghost" size="sm" asChild className="-ml-2">
          <Link href="/dashboard/planificacoes">
            <ArrowLeft className="mr-1 size-4" />
            Voltar às planificações
          </Link>
        </Button>

        {/* Hero da disciplina */}
        <div className="relative overflow-hidden rounded-2xl border bg-gradient-to-br from-primary/5 via-background to-background p-6 sm:p-8">
          <div
            className="pointer-events-none absolute -right-16 -top-16 size-56 rounded-full bg-primary/10 blur-3xl"
            aria-hidden
          />
          <div className="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-start gap-4">
              <div className="flex size-14 shrink-0 items-center justify-center rounded-xl bg-primary/15 ring-1 ring-primary/20">
                <BookOpen className="size-7 text-primary" />
              </div>
              <div className="space-y-1">
                <p className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                  Disciplina
                </p>
                <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">
                  {disciplina.nome}
                </h1>
                {disciplina.sigla && (
                  <Badge variant="secondary" className="font-mono text-[11px]">
                    {disciplina.sigla}
                  </Badge>
                )}
              </div>
            </div>

            {can.create && (
              <Button asChild size="lg" className="shadow-sm">
                <Link href={planificacoesCreate().url}>
                  <Plus className="mr-1.5 size-4" />
                  Nova planificação
                </Link>
              </Button>
            )}
          </div>

          {/* Stats */}
          {planificacoes.length > 0 && (
            <div className="relative mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
              <div className="rounded-xl border bg-background/60 p-3 backdrop-blur-sm">
                <div className="flex items-center gap-2 text-muted-foreground">
                  <FileText className="size-3.5" />
                  <span className="text-[11px] font-medium uppercase tracking-wide">
                    Planificações
                  </span>
                </div>
                <p className="mt-1 text-2xl font-bold">{stats.total}</p>
              </div>

              <div className="rounded-xl border bg-background/60 p-3 backdrop-blur-sm">
                <div className="flex items-center gap-2 text-muted-foreground">
                  <Layers className="size-3.5" />
                  <span className="text-[11px] font-medium uppercase tracking-wide">
                    Classes
                  </span>
                </div>
                <p className="mt-1 text-2xl font-bold">{stats.classes}</p>
              </div>

              {stats.ultima && (
                <div className="col-span-2 rounded-xl border bg-background/60 p-3 backdrop-blur-sm sm:col-span-1">
                  <div className="flex items-center gap-2 text-muted-foreground">
                    <TrendingUp className="size-3.5" />
                    <span className="text-[11px] font-medium uppercase tracking-wide">
                      Última atualização
                    </span>
                  </div>
                  <p className="mt-1 truncate text-sm font-semibold">
                    {stats.ultima.atualizada_em}
                  </p>
                </div>
              )}
            </div>
          )}
        </div>

        {/* Busca — só se há conteúdo */}
        {planificacoes.length > 0 && (
          <div className="relative">
            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              value={busca}
              onChange={(e) => setBusca(e.target.value)}
              placeholder="Buscar por título, período, classe ou ano letivo…"
              className="pl-9"
            />
          </div>
        )}

        {/* Lista */}
        {planificacoes.length === 0 ? (
          <Empty className="rounded-2xl border border-dashed">
            <EmptyHeader>
              <EmptyMedia variant="icon">
                <BookOpen />
              </EmptyMedia>
              <EmptyTitle>Sem planificações</EmptyTitle>
              <EmptyDescription>
                Esta disciplina ainda não tem planificações publicadas.
                {can.create && ' Comece por criar a primeira.'}
              </EmptyDescription>
            </EmptyHeader>
            {can.create && (
              <Button asChild className="mt-4">
                <Link href={planificacoesCreate().url}>
                  <Plus className="mr-1.5 size-4" />
                  Criar planificação
                </Link>
              </Button>
            )}
          </Empty>
        ) : grupos.length === 0 ? (
          <Empty className="rounded-2xl border border-dashed">
            <EmptyHeader>
              <EmptyMedia variant="icon">
                <Search />
              </EmptyMedia>
              <EmptyTitle>Sem resultados</EmptyTitle>
              <EmptyDescription>
                Nenhuma planificação corresponde a "{busca}".
              </EmptyDescription>
            </EmptyHeader>
            <Button
              variant="outline"
              className="mt-4"
              onClick={() => setBusca('')}
            >
              Limpar busca
            </Button>
          </Empty>
        ) : (
          <div className="space-y-8">
            {grupos.map(({ classe, items }) => (
              <section key={classe.id} className="space-y-4">
                {/* Header da classe */}
                <div className="flex items-center gap-3 border-b pb-3">
                  <div className="flex size-9 items-center justify-center rounded-lg bg-primary/10">
                    <GraduationCap className="size-4.5 text-primary" />
                  </div>
                  <div className="flex-1">
                    <h2 className="text-base font-semibold leading-none">
                      {classe.nome}
                    </h2>
                    <p className="mt-1 text-xs text-muted-foreground">
                      {items.length}{' '}
                      {items.length === 1
                        ? 'planificação'
                        : 'planificações'}
                    </p>
                  </div>
                  <Badge variant="secondary" className="font-mono">
                    {items.length}
                  </Badge>
                </div>

                {/* Grid de planificações */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                  {items.map((p) => (
                    <Card
                      key={p.id}
                      className="group relative flex flex-col overflow-hidden transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-lg"
                    >
                      {/* Faixa lateral decorativa */}
                      <div
                        className="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-primary/60 to-primary/20 opacity-0 transition-opacity group-hover:opacity-100"
                        aria-hidden
                      />

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

                        <CardTitle className="mt-3 line-clamp-2 text-base leading-snug">
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
                            <span className="inline-flex items-center gap-1 rounded-md bg-muted/60 px-2 py-1">
                              <Calendar className="size-3" />
                              {p.ano_letivo}
                            </span>
                          )}
                          {p.classe?.nome && (
                            <span className="inline-flex items-center gap-1 rounded-md bg-muted/60 px-2 py-1">
                              <GraduationCap className="size-3" />
                              {p.classe.nome}
                            </span>
                          )}
                        </div>
                      </CardContent>

                      <div className="border-t bg-muted/20 p-3">
                        <Button
                          variant="outline"
                          asChild
                          className="w-full transition-colors group-hover:border-primary/40 group-hover:bg-primary/5 group-hover:text-primary"
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

        {/* Contador no fundo quando há busca */}
        {temBusca && grupos.length > 0 && (
          <p className="text-center text-xs text-muted-foreground">
            A mostrar{' '}
            <span className="font-semibold text-foreground">
              {grupos.reduce((acc, g) => acc + g.items.length, 0)}
            </span>{' '}
            de {planificacoes.length} planificações
          </p>
        )}
      </div>
    </>
  );
}