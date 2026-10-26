import { Head, Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import {
  Filter,
  Search,
  X,
  Sparkles,
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
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardAction,
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
import { EmptyState } from '@/components/empty-state';
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
  const isAluno = userRole === 'aluno';

  const disciplinasFiltradas = useMemo(() => {
    const termo = busca.trim().toLowerCase();
    if (!termo) return disciplinas;

    return disciplinas.filter(
      (d) =>
        (d.nome ?? '').toLowerCase().includes(termo) ||
        (d.sigla ?? '').toLowerCase().includes(termo),
    );
  }, [disciplinas, busca]);

  const aplicarFiltros = (novos = filtros) => {
    router.get(planificacoesIndex().url, novos, {
      preserveScroll: true,
      preserveState: true,
    });
  };

  const filtrosAtivos = Boolean(
    filtros.curso_id ||
      filtros.disciplina_id ||
      filtros.classe_id ||
      filtros.periodo ||
      filtros.ano_letivo_id !== (anoAtivoId ?? ''),
  );

  const stats = useMemo(() => {
    const totalPlanificacoes = disciplinas.reduce(
      (acc, d) => acc + (d.total ?? 0),
      0,
    );
    return {
      disciplinas: disciplinas.length,
      planificacoes: totalPlanificacoes,
    };
  }, [disciplinas]);

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
    aplicarFiltros(novos);
  };

  return (
    <>
      <Head title="Planificações" />

      <div className="mx-auto w-full max-w-7xl space-y-4 p-4 sm:p-6">
        {/* Header + Filtros */}
        <Card className="gap-0! overflow-visible pb-0">
          <CardHeader className="border-b border-foreground/10">
            <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div className="min-w-0 md:flex-1">
                <Breadcrumb>
                  <BreadcrumbList className="flex-wrap">
                    <BreadcrumbItem className="min-w-0">
                      <BreadcrumbLink asChild>
                        <span className="line-clamp-2 text-sm font-semibold text-primary">
                          {isAluno ? 'Aluno' : 'Gestão'}
                        </span>
                      </BreadcrumbLink>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem className="min-w-0">
                      <BreadcrumbPage className="line-clamp-2 text-sm font-semibold text-secondary">
                        Planificações
                      </BreadcrumbPage>
                    </BreadcrumbItem>
                  </BreadcrumbList>
                </Breadcrumb>
                <CardDescription>
                  {isAluno
                    ? 'Consulte as planificações das suas disciplinas.'
                    : 'Selecione uma disciplina para ver as planificações.'}
                </CardDescription>
              </div>

              <CardAction className="flex flex-wrap items-center gap-2 self-stretch md:self-center">
                {!isAluno && disciplinas.length > 0 && (
                  <>
                    <Badge variant="outline" className="text-[10px]">
                      {stats.disciplinas}{' '}
                      {stats.disciplinas === 1 ? 'disciplina' : 'disciplinas'}
                    </Badge>
                    <Badge variant="outline" className="text-[10px]">
                      {stats.planificacoes}{' '}
                      {stats.planificacoes === 1
                        ? 'planificação'
                        : 'planificações'}
                    </Badge>
                  </>
                )}
                {can.create && (
                  <Button asChild size="sm" className="hover:cursor-pointer">
                    <Link href={planificacoesCreate().url}>
                      Nova planificação
                    </Link>
                  </Button>
                )}
              </CardAction>
            </div>
          </CardHeader>

          {/* Filtros */}
          <CardContent className="space-y-3 p-4">
            <div className="flex items-center justify-between">
              <h2 className="text-sm font-semibold whitespace-nowrap">
                Filtros
              </h2>
            </div>

            {isAluno ? (
              /* Aluno: só período */
              <div className="flex flex-wrap items-center gap-2">
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                  <Filter className="size-4" />
                  <span className="font-medium">Período:</span>
                </div>
                <Select
                  value={filtros.periodo || 'todos'}
                  onValueChange={(v) => {
                    const periodo = v === 'todos' ? '' : v;
                    const novos = { ...filtros, periodo };
                    setFiltros(novos);
                    aplicarFiltros(novos);
                  }}
                >
                  <SelectTrigger className="w-full sm:w-56">
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
              /* Staff */
              <>
                <div className="relative">
                  <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                  <Input
                    placeholder="Pesquisar disciplina por nome ou sigla…"
                    value={busca}
                    onChange={(e) => setBusca(e.target.value)}
                    className="pr-9 pl-9"
                  />
                  {busca && (
                    <button
                      type="button"
                      onClick={() => setBusca('')}
                      className="absolute right-2 top-1/2 -translate-y-1/2 p-1 hover:bg-muted"
                    >
                      <X className="size-4 text-muted-foreground" />
                    </button>
                  )}
                </div>

                {chipsFiltros.length > 0 && (
                  <div className="flex flex-wrap items-center gap-2">
                    {chipsFiltros.map((chip) => (
                      <Badge
                        key={chip.key}
                        variant="outline"
                        className="gap-1 py-1 pl-2 pr-1"
                      >
                        {chip.label}
                        <button
                          type="button"
                          onClick={() => removerChip(chip.key)}
                          className="p-0.5 hover:bg-background/60"
                          aria-label={`Remover filtro ${chip.label}`}
                        >
                          <X className="size-3" />
                        </button>
                      </Badge>
                    ))}
                  </div>
                )}

                <div className="flex flex-wrap items-center gap-2">
                  <Select
                    value={filtros.ano_letivo_id}
                    onValueChange={(v) => {
                      const novos = { ...filtros, ano_letivo_id: v };
                      setFiltros(novos);
                      aplicarFiltros(novos);
                    }}
                  >
                    <SelectTrigger className="w-full sm:w-44">
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
                    value={filtros.curso_id || 'todos'}
                    onValueChange={(v) => {
                      const novos = {
                        ...filtros,
                        curso_id: v === 'todos' ? '' : v,
                      };
                      setFiltros(novos);
                      aplicarFiltros(novos);
                    }}
                  >
                    <SelectTrigger className="w-full sm:w-48">
                      <SelectValue placeholder="Curso" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="todos">Todos os cursos</SelectItem>
                      {cursos.map((c) => (
                        <SelectItem key={c.id} value={c.id}>
                          {c.nome}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>

                  <Select
                    value={filtros.classe_id || 'todas'}
                    onValueChange={(v) => {
                      const novos = {
                        ...filtros,
                        classe_id: v === 'todas' ? '' : v,
                      };
                      setFiltros(novos);
                      aplicarFiltros(novos);
                    }}
                  >
                    <SelectTrigger className="w-full sm:w-44">
                      <SelectValue placeholder="Classe" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="todas">Todas as classes</SelectItem>
                      {classes.map((c) => (
                        <SelectItem key={c.id} value={c.id}>
                          {c.nome}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>

                  <Select
                    value={filtros.periodo || 'todos'}
                    onValueChange={(v) => {
                      const novos = {
                        ...filtros,
                        periodo: v === 'todos' ? '' : v,
                      };
                      setFiltros(novos);
                      aplicarFiltros(novos);
                    }}
                  >
                    <SelectTrigger className="w-full sm:w-40">
                      <SelectValue placeholder="Período" />
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
              </>
            )}
          </CardContent>
        </Card>

        {/* Resumo da busca */}
        {busca && !isAluno && (
          <p className="text-sm text-muted-foreground">
            {disciplinasFiltradas.length === 0 ? (
              'Nenhuma disciplina corresponde à pesquisa.'
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
          <EmptyState
            variant="table"
            icon={busca ? Search : Sparkles}
            title={busca ? 'Sem resultados' : 'Sem planificações'}
            description={
              busca
                ? 'Tente ajustar a pesquisa ou limpar os filtros.'
                : can.create
                  ? 'Ainda não foi criada nenhuma planificação para os filtros atuais.'
                  : 'Ainda não há planificações disponíveis para você.'
            }
          />
        ) : (
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {disciplinasFiltradas.map((d) => (
              <Link
                key={d.id}
                href={`/dashboard/planificacoes/por-disciplina/${d.id}`}
                className="group block h-full"
              >
                <Card className="flex h-full flex-col overflow-hidden transition-shadow hover:shadow-md">
                  <CardHeader className="pb-3">
                    <div className="flex min-w-0 items-start justify-between gap-3">
                      <div className="min-w-0 flex-1">
                        <CardTitle
                          className="truncate text-base leading-tight"
                          title={d.nome}
                        >
                          {d.nome}
                        </CardTitle>
                        {d.sigla && (
                          <CardDescription className="mt-0.5 truncate font-mono text-xs">
                            {d.sigla}
                          </CardDescription>
                        )}
                      </div>
                    </div>
                  </CardHeader>

                  <CardContent className="flex-1 space-y-2 pb-4">
                    <div className="flex flex-wrap gap-1.5">
                      <Badge variant="outline" className="font-normal">
                        {d.total}{' '}
                        {d.total === 1 ? 'planificação' : 'planificações'}
                      </Badge>
                      {d.total_classes > 0 && (
                        <Badge variant="outline" className="font-normal">
                          {d.total_classes}{' '}
                          {d.total_classes === 1 ? 'classe' : 'classes'}
                        </Badge>
                      )}
                      {d.total_cursos > 0 && (
                        <Badge variant="outline" className="font-normal">
                          {d.total_cursos}{' '}
                          {d.total_cursos === 1 ? 'curso' : 'cursos'}
                        </Badge>
                      )}
                    </div>
                  </CardContent>

                  <div className="border-t bg-muted/20 px-4 py-2.5">
                    <p className="truncate text-[11px] text-muted-foreground">
                      Última atualização: {d.ultima_atualizacao ?? '—'}
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