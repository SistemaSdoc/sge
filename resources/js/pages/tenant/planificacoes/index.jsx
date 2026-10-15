import { Head, Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import {
  BookOpen,
  Plus,
  Filter,
  ChevronRight,
  Hash,
  Clock,
  Layers,
  Search,
  X,
  GraduationCap,
  CalendarDays,
  Sparkles,
  SlidersHorizontal,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from '@/components/ui/empty';
import {
  index as planificacoesIndex,
  create as planificacoesCreate,
} from '@/actions/App/Http/Controllers/Tenant/PlanificacaoController';

export default function Index({
  disciplinas = [],
  cursos = [],
  filters = {},
  classes = [],
  anosLectivos = [],
  anoAtivoId,
  periodos = [],
  userRole = 'staff',
  can = {},
}) {
  const [filtros, setFiltros] = useState({
    ano_letivo_id: filters.ano_letivo_id ?? anoAtivoId ?? '',
    curso_id: filters.curso_id ?? '',
    disciplina_id: filters.disciplina_id ?? '',
    classe_id: filters.classe_id ?? '',
    periodo: filters.periodo ?? '',
  });

  const [busca, setBusca] = useState('');
  const [filtrosExpandidos, setFiltrosExpandidos] = useState(false);

  const isAluno = userRole === 'aluno';

  const disciplinasFiltradas = useMemo(() => {
    const termo = busca.trim().toLowerCase();
    if (!termo) return disciplinas;

    return disciplinas.filter(
      (d) =>
        (d.nome ?? '').toLowerCase().includes(termo) ||
        (d.sigla ?? '').toLowerCase().includes(termo)
    );
  }, [disciplinas, busca]);

  const aplicarFiltros = () => {
    router.get(planificacoesIndex().url, filtros, {
      preserveScroll: true,
      preserveState: true,
    });
  };

  const limparFiltros = () => {
    setFiltros({
      ano_letivo_id: anoAtivoId ?? '',
      curso_id: '',
      disciplina_id: '',
      classe_id: '',
      periodo: '',
    });
    setBusca('');
    router.get(planificacoesIndex().url, {}, { preserveScroll: true });
  };

  const filtrosAtivos =
    filtros.curso_id ||
    filtros.disciplina_id ||
    filtros.classe_id ||
    filtros.periodo ||
    filtros.ano_letivo_id !== (anoAtivoId ?? '');

  // Estatísticas agregadas
  const stats = useMemo(() => {
    const totalPlanificacoes = disciplinas.reduce(
      (acc, d) => acc + (d.total ?? 0),
      0
    );
    const totalClasses = disciplinas.reduce(
      (acc, d) => acc + (d.total_classes ?? 0),
      0
    );
    const totalCursos = disciplinas.reduce(
      (acc, d) => acc + (d.total_cursos ?? 0),
      0
    );
    return {
      disciplinas: disciplinas.length,
      planificacoes: totalPlanificacoes,
      classes: totalClasses,
      cursos: totalCursos,
    };
  }, [disciplinas]);

  // Chips de filtros ativos
  const chipsFiltros = useMemo(() => {
    const chips = [];

    if (filtros.ano_letivo_id && filtros.ano_letivo_id !== (anoAtivoId ?? '')) {
      const ano = anosLectivos.find((a) => a.id === filtros.ano_letivo_id);
      if (ano) chips.push({ label: ano.nome, key: 'ano_letivo_id' });
    }
    if (filtros.curso_id) {
      const c = cursos.find((x) => x.id === filtros.curso_id);
      if (c) chips.push({ label: c.nome, key: 'curso_id' });
    }
    if (filtros.classe_id) {
      const c = classes.find((x) => x.id === filtros.classe_id);
      if (c) chips.push({ label: c.nome, key: 'classe_id' });
    }
    if (filtros.periodo) {
      chips.push({ label: filtros.periodo, key: 'periodo' });
    }

    return chips;
  }, [filtros, anosLectivos, cursos, classes, anoAtivoId]);

  const removerChip = (key) => {
    const novos = { ...filtros, [key]: '' };
    setFiltros(novos);
    router.get(planificacoesIndex().url, novos, {
      preserveScroll: true,
      preserveState: true,
    });
  };

  return (
    <>
      <Head title="Planificações" />

      <div className="mx-auto w-full max-w-7xl space-y-6 p-4 sm:p-6">
        {/* Hero */}
        <div className="relative overflow-hidden rounded-2xl border bg-gradient-to-br from-primary/5 via-background to-background p-6 sm:p-8">
          <div
            className="pointer-events-none absolute -right-20 -top-20 size-64 rounded-full bg-primary/10 blur-3xl"
            aria-hidden
          />
          <div className="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-start gap-4">
              <div className="flex size-14 shrink-0 items-center justify-center rounded-xl bg-primary/15 ring-1 ring-primary/20">
                <BookOpen className="size-7 text-primary" />
              </div>
              <div className="space-y-1">
                <p className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                  {isAluno ? 'As minhas disciplinas' : 'Gestão'}
                </p>
                <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">
                  Planificações
                </h1>
                <p className="text-sm text-muted-foreground">
                  {isAluno
                    ? 'Consulte as planificações das suas disciplinas.'
                    : 'Selecione uma disciplina para ver as planificações.'}
                </p>
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
          {!isAluno && disciplinas.length > 0 && (
            <div className="relative mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
              <div className="rounded-xl border bg-background/60 p-3 backdrop-blur-sm">
                <div className="flex items-center gap-2 text-muted-foreground">
                  <BookOpen className="size-3.5" />
                  <span className="text-[11px] font-medium uppercase tracking-wide">
                    Disciplinas
                  </span>
                </div>
                <p className="mt-1 text-2xl font-bold">{stats.disciplinas}</p>
              </div>

              <div className="rounded-xl border bg-background/60 p-3 backdrop-blur-sm">
                <div className="flex items-center gap-2 text-muted-foreground">
                  <Layers className="size-3.5" />
                  <span className="text-[11px] font-medium uppercase tracking-wide">
                    Planificações
                  </span>
                </div>
                <p className="mt-1 text-2xl font-bold">{stats.planificacoes}</p>
              </div>

              <div className="rounded-xl border bg-background/60 p-3 backdrop-blur-sm">
                <div className="flex items-center gap-2 text-muted-foreground">
                  <Hash className="size-3.5" />
                  <span className="text-[11px] font-medium uppercase tracking-wide">
                    Classes
                  </span>
                </div>
                <p className="mt-1 text-2xl font-bold">{stats.classes}</p>
              </div>

              <div className="rounded-xl border bg-background/60 p-3 backdrop-blur-sm">
                <div className="flex items-center gap-2 text-muted-foreground">
                  <GraduationCap className="size-3.5" />
                  <span className="text-[11px] font-medium uppercase tracking-wide">
                    Cursos
                  </span>
                </div>
                <p className="mt-1 text-2xl font-bold">{stats.cursos}</p>
              </div>
            </div>
          )}
        </div>

        {/* Filtros — Aluno */}
        {isAluno ? (
          <div className="flex flex-wrap items-center gap-3 rounded-xl border bg-muted/30 p-3">
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <Filter className="size-4" />
              <span className="font-medium">Filtrar por período:</span>
            </div>

            <Select
              value={filtros.periodo || 'todos'}
              onValueChange={(v) => {
                const periodo = v === 'todos' ? '' : v;
                setFiltros((prev) => ({ ...prev, periodo }));
                router.get(
                  planificacoesIndex().url,
                  { ...filtros, periodo },
                  { preserveScroll: true, preserveState: true }
                );
              }}
            >
              <SelectTrigger className="w-[220px]">
                <SelectValue placeholder="Todos os períodos" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="todos">Todos os períodos</SelectItem>
                {periodos.map((p) => (
                  <SelectItem key={p} value={p}>
                    {p}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        ) : (
          <Card className="overflow-hidden">
            <CardContent className="space-y-3 pt-6">
              {/* Pesquisa + toggle filtros */}
              <div className="flex flex-col gap-2 sm:flex-row">
                <div className="relative flex-1">
                  <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                  <Input
                    placeholder="Pesquisar disciplina por nome ou sigla…"
                    value={busca}
                    onChange={(e) => setBusca(e.target.value)}
                    className="pl-9 pr-9"
                  />
                  {busca && (
                    <button
                      type="button"
                      onClick={() => setBusca('')}
                      className="absolute right-2 top-1/2 -translate-y-1/2 rounded-full p-1 hover:bg-muted"
                    >
                      <X className="size-4 text-muted-foreground" />
                    </button>
                  )}
                </div>

                <Button
                  variant={filtrosExpandidos ? 'secondary' : 'outline'}
                  onClick={() => setFiltrosExpandidos((v) => !v)}
                  className="sm:w-auto"
                >
                  <SlidersHorizontal className="mr-1.5 size-4" />
                  Filtros
                  {filtrosAtivos && (
                    <Badge
                      variant="default"
                      className="ml-2 h-5 min-w-5 rounded-full px-1.5 text-[10px]"
                    >
                      {chipsFiltros.length || 1}
                    </Badge>
                  )}
                </Button>
              </div>

              {/* Chips de filtros ativos */}
              {chipsFiltros.length > 0 && (
                <div className="flex flex-wrap items-center gap-2">
                  {chipsFiltros.map((chip) => (
                    <Badge
                      key={chip.key}
                      variant="secondary"
                      className="gap-1 py-1 pl-2 pr-1"
                    >
                      {chip.label}
                      <button
                        type="button"
                        onClick={() => removerChip(chip.key)}
                        className="rounded-full p-0.5 hover:bg-background/60"
                        aria-label={`Remover filtro ${chip.label}`}
                      >
                        <X className="size-3" />
                      </button>
                    </Badge>
                  ))}
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={limparFiltros}
                    className="h-6 px-2 text-xs text-muted-foreground"
                  >
                    Limpar tudo
                  </Button>
                </div>
              )}

              {/* Filtros detalhados */}
              <div
                className={`grid transition-all duration-200 ${
                  filtrosExpandidos
                    ? 'grid-rows-[1fr] opacity-100'
                    : 'grid-rows-[0fr] opacity-0'
                }`}
              >
                <div className="overflow-hidden">
                  <div className="grid grid-cols-1 gap-3 pt-1 sm:grid-cols-2 lg:grid-cols-5">
                    <Select
                      value={filtros.ano_letivo_id}
                      onValueChange={(v) =>
                        setFiltros((prev) => ({ ...prev, ano_letivo_id: v }))
                      }
                    >
                      <SelectTrigger>
                        <CalendarDays className="mr-2 size-3.5 text-muted-foreground" />
                        <SelectValue placeholder="Ano letivo" />
                      </SelectTrigger>
                      <SelectContent>
                        {anosLectivos.map((a) => (
                          <SelectItem key={a.id} value={a.id}>
                            {a.nome}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>

                    <Select
                      value={filtros.curso_id}
                      onValueChange={(v) =>
                        setFiltros((prev) => ({ ...prev, curso_id: v }))
                      }
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Curso" />
                      </SelectTrigger>
                      <SelectContent>
                        {cursos.map((c) => (
                          <SelectItem key={c.id} value={c.id}>
                            {c.nome}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>

                    <Select
                      value={filtros.classe_id}
                      onValueChange={(v) =>
                        setFiltros((prev) => ({ ...prev, classe_id: v }))
                      }
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Classe" />
                      </SelectTrigger>
                      <SelectContent>
                        {classes.map((c) => (
                          <SelectItem key={c.id} value={c.id}>
                            {c.nome}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>

                    <Select
                      value={filtros.periodo}
                      onValueChange={(v) =>
                        setFiltros((prev) => ({ ...prev, periodo: v }))
                      }
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Período" />
                      </SelectTrigger>
                      <SelectContent>
                        {periodos.map((p) => (
                          <SelectItem key={p} value={p}>
                            {p}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>

                    <div className="flex gap-2">
                      <Button onClick={aplicarFiltros} className="flex-1">
                        <Filter className="mr-1 size-4" />
                        Aplicar
                      </Button>
                      {filtrosAtivos && (
                        <Button variant="outline" onClick={limparFiltros}>
                          Limpar
                        </Button>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        )}

        {/* Resumo da busca */}
        {busca && !isAluno && (
          <p className="text-sm text-muted-foreground">
            {disciplinasFiltradas.length === 0 ? (
              <>Nenhuma disciplina corresponde à pesquisa.</>
            ) : (
              <>
                A mostrar{' '}
                <span className="font-semibold text-foreground">
                  {disciplinasFiltradas.length}
                </span>{' '}
                de {disciplinas.length} disciplinas
              </>
            )}
          </p>
        )}

        {/* Lista */}
        {disciplinasFiltradas.length === 0 ? (
          <Empty className="rounded-2xl border border-dashed">
            <EmptyHeader>
              <EmptyMedia variant="icon">
                {busca ? <Search /> : <Sparkles />}
              </EmptyMedia>
              <EmptyTitle>
                {busca ? 'Sem resultados' : 'Sem planificações'}
              </EmptyTitle>
              <EmptyDescription>
                {busca
                  ? 'Tente ajustar a pesquisa ou limpar os filtros.'
                  : can.create
                    ? 'Ainda não foi criada nenhuma planificação para os filtros atuais.'
                    : 'Ainda não há planificações disponíveis para si.'}
              </EmptyDescription>
            </EmptyHeader>
            {(busca || filtrosAtivos) && (
              <Button variant="outline" className="mt-4" onClick={limparFiltros}>
                Limpar filtros e pesquisa
              </Button>
            )}
          </Empty>
        ) : (
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {disciplinasFiltradas.map((d) => (
              <Link
                key={d.id}
                href={`/dashboard/planificacoes/por-disciplina/${d.id}`}
                className="group"
              >
                <Card className="relative flex h-full flex-col overflow-hidden transition-all duration-200 group-hover:-translate-y-0.5 group-hover:border-primary/40 group-hover:shadow-lg">
                  {/* Faixa lateral decorativa */}
                  <div
                    className="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-primary/60 to-primary/20 opacity-0 transition-opacity group-hover:opacity-100"
                    aria-hidden
                  />

                  <CardHeader className="pb-3">
                    <div className="flex items-start justify-between gap-3">
                      <div className="flex min-w-0 items-center gap-3">
                        <div className="flex size-11 shrink-0 items-center justify-center rounded-lg bg-primary/10 transition-colors group-hover:bg-primary/15">
                          <BookOpen className="size-5 text-primary" />
                        </div>
                        <div className="min-w-0">
                          <CardTitle className="truncate text-base leading-tight">
                            {d.nome}
                          </CardTitle>
                          {d.sigla && (
                            <CardDescription className="mt-0.5 font-mono text-xs">
                              {d.sigla}
                            </CardDescription>
                          )}
                        </div>
                      </div>
                      <ChevronRight className="size-5 shrink-0 text-muted-foreground transition-all group-hover:translate-x-0.5 group-hover:text-primary" />
                    </div>
                  </CardHeader>

                  <CardContent className="flex-1 space-y-2 pb-4">
                    <div className="flex flex-wrap gap-1.5">
                      <Badge variant="secondary" className="gap-1 font-normal">
                        <Layers className="size-3" />
                        {d.total}{' '}
                        {d.total === 1 ? 'planificação' : 'planificações'}
                      </Badge>
                      {d.total_classes > 0 && (
                        <Badge variant="outline" className="gap-1 font-normal">
                          <Hash className="size-3" />
                          {d.total_classes}{' '}
                          {d.total_classes === 1 ? 'classe' : 'classes'}
                        </Badge>
                      )}
                      {d.total_cursos > 0 && (
                        <Badge variant="outline" className="gap-1 font-normal">
                          <GraduationCap className="size-3" />
                          {d.total_cursos}{' '}
                          {d.total_cursos === 1 ? 'curso' : 'cursos'}
                        </Badge>
                      )}
                    </div>
                  </CardContent>

                  <div className="border-t bg-muted/20 px-4 py-2.5">
                    <p className="flex items-center gap-1.5 text-[11px] text-muted-foreground">
                      <Clock className="size-3 shrink-0" />
                      <span className="truncate">
                        Última atualização: {d.ultima_atualizacao ?? '—'}
                      </span>
                    </p>
                  </div>
                </Card>
              </Link>
            ))}
          </div>
        )}
      </div>
    </>
  );
}