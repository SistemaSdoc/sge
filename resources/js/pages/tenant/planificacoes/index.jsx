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

  const isAluno = userRole === 'aluno';

  const disciplinasFiltradas = useMemo(() => {
    const termo = busca.trim().toLowerCase();
    if (!termo) return disciplinas;

    return disciplinas.filter((d) =>
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
    filtros.curso_id || filtros.disciplina_id || filtros.classe_id || filtros.periodo ||
    filtros.ano_letivo_id !== (anoAtivoId ?? '');

  return (
    <>
      <Head title="Planificações" />

      <div className="mx-auto w-full max-w-7xl space-y-6 p-4 sm:p-6">
        {/* Cabeçalho */}
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">Planificações</h1>
            <p className="mt-1 text-sm text-muted-foreground">
              {isAluno
                ? 'Consulte as planificações das suas disciplinas.'
                : 'Selecione uma disciplina para ver as planificações.'}
            </p>
          </div>

          {can.create && (
            <Button asChild>
              <Link href={planificacoesCreate().url}>
                <Plus className="mr-1.5 size-4" />
                Nova planificação
              </Link>
            </Button>
          )}
        </div>

        {/* Filtros */}
        {isAluno ? (
          <div className="flex flex-wrap items-center gap-3 rounded-lg border bg-muted/30 p-3">
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
              <SelectTrigger className="w-[200px]">
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
          <Card>
            <CardContent className="space-y-3 pt-6">
              {/* Pesquisa */}
              <div className="relative">
                <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                  placeholder="Pesquisar disciplina por nome ou sigla..."
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

              {/* Filtros */}
              <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <Select
                  value={filtros.ano_letivo_id}
                  onValueChange={(v) => setFiltros((prev) => ({ ...prev, ano_letivo_id: v }))}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Ano letivo" />
                  </SelectTrigger>
                  <SelectContent>
                    {anosLectivos.map((a) => (
                      <SelectItem key={a.id} value={a.id}>{a.nome}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>

                <Select
                  value={filtros.curso_id}
                  onValueChange={(v) => setFiltros((prev) => ({ ...prev, curso_id: v }))}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Curso" />
                  </SelectTrigger>
                  <SelectContent>
                    {cursos.map((c) => (
                      <SelectItem key={c.id} value={c.id}>{c.nome}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>

                <Select
                  value={filtros.classe_id}
                  onValueChange={(v) => setFiltros((prev) => ({ ...prev, classe_id: v }))}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Classe" />
                  </SelectTrigger>
                  <SelectContent>
                    {classes.map((c) => (
                      <SelectItem key={c.id} value={c.id}>{c.nome}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>

                <Select
                  value={filtros.periodo}
                  onValueChange={(v) => setFiltros((prev) => ({ ...prev, periodo: v }))}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Período" />
                  </SelectTrigger>
                  <SelectContent>
                    {periodos.map((p) => (
                      <SelectItem key={p} value={p}>{p}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>

                <div className="flex gap-2">
                  <Button onClick={aplicarFiltros} className="flex-1">
                    <Filter className="mr-1 size-4" />
                    Filtrar
                  </Button>
                  {filtrosAtivos && (
                    <Button variant="outline" onClick={limparFiltros}>
                      Limpar
                    </Button>
                  )}
                </div>
              </div>
            </CardContent>
          </Card>
        )}

        {/* Resumo */}
        {busca && !isAluno && (
          <p className="text-sm text-muted-foreground">
            {disciplinasFiltradas.length === 0
              ? 'Nenhuma disciplina corresponde à pesquisa.'
              : `${disciplinasFiltradas.length} de ${disciplinas.length} disciplinas`}
          </p>
        )}

        {/* Lista */}
        {disciplinasFiltradas.length === 0 ? (
          <Empty>
            <EmptyHeader>
              <EmptyMedia variant="icon"><BookOpen /></EmptyMedia>
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
          </Empty>
        ) : (
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {disciplinasFiltradas.map((d) => (
              <Link
                key={d.id}
                href={`/dashboard/planificacoes/por-disciplina/${d.id}`}
                className="group"
              >
                <Card className="flex h-full flex-col transition-all group-hover:border-primary/50 group-hover:shadow-md">
                  <CardHeader className="pb-3">
                    <div className="flex items-start justify-between gap-3">
                      <div className="flex min-w-0 items-center gap-3">
                        <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                          <BookOpen className="size-5 text-primary" />
                        </div>
                        <div className="min-w-0">
                          <CardTitle className="truncate text-base">
                            {d.nome}
                          </CardTitle>
                          {d.sigla && (
                            <CardDescription className="text-xs">
                              {d.sigla}
                            </CardDescription>
                          )}
                        </div>
                      </div>
                      <ChevronRight className="size-5 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:text-primary" />
                    </div>
                  </CardHeader>

                  <CardContent className="flex-1 space-y-2 pb-4">
                    <div className="flex flex-wrap gap-2">
                      <Badge variant="secondary" className="gap-1">
                        <Layers className="size-3" />
                        {d.total} {d.total === 1 ? 'planificação' : 'planificações'}
                      </Badge>
                      {d.total_classes > 0 && (
                        <Badge variant="outline" className="gap-1">
                          <Hash className="size-3" />
                          {d.total_classes} {d.total_classes === 1 ? 'classe' : 'classes'}
                        </Badge>
                      )}
                      {d.total_cursos > 0 && (
                        <Badge variant="outline" className="gap-1">
                          <GraduationCap className="size-3" />
                          {d.total_cursos} {d.total_cursos === 1 ? 'curso' : 'cursos'}
                        </Badge>
                      )}
                    </div>
                  </CardContent>

                  <div className="border-t px-4 py-2.5">
                    <p className="flex items-center gap-1 text-[11px] text-muted-foreground">
                      <Clock className="size-3" />
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