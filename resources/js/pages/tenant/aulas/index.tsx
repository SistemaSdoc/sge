import { Head, Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import {
  Calendar,
  ChevronLeft,
  ChevronRight,
  Eye,
  Filter,
  Search,
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { EmptyState } from '@/components/empty-state';

interface Aula {
  id: string;
  data: string;
  data_humana: string;
  hora_inicio: string;
  hora_fim: string;
  turma_nome: string;
  disciplina_nome: string;
  professor_nome: string | null;
  tipo_label: string;
  titulo: string | null;
  sumario: string | null;
  estado: 'rascunho' | 'publicado' | 'cancelado';
  estado_label: string;
  tem_sumario: boolean;
  urls: { ver: string };
}

interface Paginacao {
  data: Aula[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
  aulas: Paginacao;
  filtros: {
    de?: string;
    ate?: string;
    disciplina_id?: string;
    professor_id?: string;
    estado?: string;
  };
  professores?: { id: string; nome: string }[];
  disciplinas?: { id: string; nome: string }[];
}

const formatarData = (data: string | null | undefined): string => {
  if (!data) return '—';
  const [year, month, day] = data.split('-');
  return `${day}/${month}/${year}`;
};

export default function Index({
  aulas,
  filtros = {},
  professores = [],
  disciplinas = [],
}: Props) {
  const [busca, setBusca] = useState('');
  const [filtrosLocais, setFiltrosLocais] = useState({
    de: filtros.de ?? '',
    ate: filtros.ate ?? '',
    disciplina_id: filtros.disciplina_id ?? '',
    professor_id: filtros.professor_id ?? '',
    estado: filtros.estado ?? '',
  });

  const aplicarFiltros = () => {
    router.get('/dashboard/aulas', filtrosLocais, {
      preserveScroll: true,
      preserveState: true,
    });
  };

  const limparFiltros = () => {
    const limpos = {
      de: '',
      ate: '',
      disciplina_id: '',
      professor_id: '',
      estado: '',
    };
    setFiltrosLocais(limpos);
    setBusca('');
    router.get('/dashboard/aulas', limpos, { preserveScroll: true });
  };

  const filtrosAtivos = Boolean(
    filtrosLocais.disciplina_id ||
      filtrosLocais.professor_id ||
      filtrosLocais.estado ||
      filtrosLocais.de ||
      filtrosLocais.ate,
  );

  const aulasFiltradas = useMemo(() => {
    const termo = busca.trim().toLowerCase();
    if (!termo) return aulas.data;

    return aulas.data.filter(
      (a) =>
        (a.disciplina_nome ?? '').toLowerCase().includes(termo) ||
        (a.turma_nome ?? '').toLowerCase().includes(termo) ||
        (a.professor_nome ?? '').toLowerCase().includes(termo) ||
        (a.titulo ?? '').toLowerCase().includes(termo),
    );
  }, [aulas.data, busca]);

  const stats = useMemo(() => {
    const comSumario = aulas.data.filter(
      (a) => a.tem_sumario && a.estado === 'publicado',
    ).length;
    const pendentes = aulas.data.filter(
      (a) => !a.tem_sumario && a.estado !== 'cancelado',
    ).length;
    const canceladas = aulas.data.filter((a) => a.estado === 'cancelado').length;
    return { comSumario, pendentes, canceladas };
  }, [aulas.data]);

  return (
    <>
      <Head title="Aulas & Sumários" />

      <div className="mx-auto w-full max-w-7xl space-y-4 p-4 sm:p-6">
        {/* ── Header + Filtros ───────────────────────────── */}
        <Card className="gap-0! overflow-visible pb-0">
          <CardHeader className="border-b border-foreground/10">
            <div className="min-w-0 space-y-1">
              <Breadcrumb>
                <BreadcrumbList className="flex-wrap">
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbLink asChild>
                      <span className="line-clamp-2 text-sm font-semibold text-primary">
                        Direção
                      </span>
                    </BreadcrumbLink>
                  </BreadcrumbItem>
                  <BreadcrumbSeparator />
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbPage className="line-clamp-2 text-sm font-semibold text-secondary">
                      Aulas & Sumários
                    </BreadcrumbPage>
                  </BreadcrumbItem>
                </BreadcrumbList>
              </Breadcrumb>
              <CardDescription>
                Acompanha o registo de aulas de todos os professores.
              </CardDescription>
            </div>
          </CardHeader>

          <div className="space-y-3 px-4 py-3">
            {/* Linha do título "Filtros" */}
            <div className="flex items-center justify-between">
              <h2 className="text-sm font-semibold whitespace-nowrap">
                Filtros
              </h2>
              {filtrosAtivos && (
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={limparFiltros}
                  className="hover:cursor-pointer"
                >
                  Limpar
                </Button>
              )}
            </div>

            {/* Busca */}
            <div className="relative">
              <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                value={busca}
                onChange={(e) => setBusca(e.target.value)}
                placeholder="Pesquisar disciplina, turma, professor…"
                className="pl-9"
              />
            </div>

            {/* Filtros em flex-wrap — sem scroll horizontal */}
            <div className="flex flex-wrap items-center gap-2">
              <Input
                type="date"
                value={filtrosLocais.de}
                onChange={(e) =>
                  setFiltrosLocais((p) => ({ ...p, de: e.target.value }))
                }
                className="w-full sm:w-40"
                aria-label="De"
              />

              <Input
                type="date"
                value={filtrosLocais.ate}
                onChange={(e) =>
                  setFiltrosLocais((p) => ({ ...p, ate: e.target.value }))
                }
                className="w-full sm:w-40"
                aria-label="Até"
              />

              <Select
                value={filtrosLocais.disciplina_id || 'todas'}
                onValueChange={(v) =>
                  setFiltrosLocais((p) => ({
                    ...p,
                    disciplina_id: v === 'todas' ? '' : v,
                  }))
                }
              >
                <SelectTrigger className="w-full sm:w-48">
                  <SelectValue placeholder="Disciplina" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="todas">Todas as disciplinas</SelectItem>
                  {disciplinas.map((d) => (
                    <SelectItem key={d.id} value={d.id}>
                      {d.nome}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>

              <Select
                value={filtrosLocais.professor_id || 'todos'}
                onValueChange={(v) =>
                  setFiltrosLocais((p) => ({
                    ...p,
                    professor_id: v === 'todos' ? '' : v,
                  }))
                }
              >
                <SelectTrigger className="w-full sm:w-48">
                  <SelectValue placeholder="Professor" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="todos">Todos os professores</SelectItem>
                  {professores.map((p) => (
                    <SelectItem key={p.id} value={p.id}>
                      {p.nome}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>

              <Select
                value={filtrosLocais.estado || 'todos'}
                onValueChange={(v) =>
                  setFiltrosLocais((p) => ({
                    ...p,
                    estado: v === 'todos' ? '' : v,
                  }))
                }
              >
                <SelectTrigger className="w-full sm:w-44">
                  <SelectValue placeholder="Estado" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="todos">Todos os estados</SelectItem>
                  <SelectItem value="rascunho">Rascunho</SelectItem>
                  <SelectItem value="publicado">Publicado</SelectItem>
                  <SelectItem value="cancelado">Cancelado</SelectItem>
                </SelectContent>
              </Select>

              <Button
                onClick={aplicarFiltros}
                className="w-full hover:cursor-pointer sm:w-auto"
              >
                <Filter className="mr-1.5 size-4" />
                Aplicar filtros
              </Button>
            </div>
          </div>
        </Card>

        {/* ── Tabela de aulas ────────────────────────────── */}
        <Card className="gap-0 pb-0">
          <CardHeader className="flex flex-col gap-3 border-b md:flex-row md:items-center">
            <div className="min-w-0 md:flex-1">
              <CardTitle>Aulas</CardTitle>
              <CardDescription>
                {aulas.total} {aulas.total === 1 ? 'aula' : 'aulas'} no período
              </CardDescription>
            </div>
            <CardAction className="flex flex-wrap gap-2">
              <Badge className="bg-green-600 hover:bg-green-600">
                ✓ {stats.comSumario} com sumário
              </Badge>
              <Badge variant="secondary">
                {stats.pendentes} sem sumário
              </Badge>
              <Badge variant="destructive">
                {stats.canceladas} canceladas
              </Badge>
            </CardAction>
          </CardHeader>

          <CardContent className="p-0!">
            {aulasFiltradas.length === 0 ? (
              <EmptyState
                variant="table"
                icon={Calendar}
                title="Sem aulas"
                description={
                  filtrosAtivos
                    ? 'Nenhuma aula corresponde aos filtros aplicados.'
                    : 'Ainda não há aulas registadas neste período.'
                }
              />
            ) : (
              <div className="w-full overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow className="bg-muted/72">
                      <TableHead className="min-w-28 px-4">Data</TableHead>
                      <TableHead className="min-w-28 px-4 text-center">
                        Hora
                      </TableHead>
                      <TableHead className="min-w-40 px-4">
                        Disciplina
                      </TableHead>
                      <TableHead className="min-w-32 px-4">Turma</TableHead>
                      <TableHead className="min-w-40 px-4">
                        Professor
                      </TableHead>
                      <TableHead className="min-w-64 px-4">Aula</TableHead>
                      <TableHead className="min-w-28 px-4 text-center">
                        Estado
                      </TableHead>
                      <TableHead className="min-w-20 px-4 text-center">
                        Acções
                      </TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {aulasFiltradas.map((aula) => (
                      <TableRow
                        key={aula.id}
                        className={
                          aula.estado === 'cancelado' ? 'opacity-60' : ''
                        }
                      >
                        <TableCell className="px-4 font-medium whitespace-nowrap">
                          {formatarData(aula.data)}
                        </TableCell>
                        <TableCell className="px-4 text-center font-mono text-xs tabular-nums whitespace-nowrap">
                          {aula.hora_inicio}–{aula.hora_fim}
                        </TableCell>
                        <TableCell className="px-4">
                          <span className="line-clamp-2">
                            {aula.disciplina_nome}
                          </span>
                        </TableCell>
                        <TableCell className="px-4">
                          <span className="line-clamp-2">
                            {aula.turma_nome}
                          </span>
                        </TableCell>
                        <TableCell className="px-4">
                          <span className="line-clamp-1 text-sm text-muted-foreground">
                            {aula.professor_nome ?? '—'}
                          </span>
                        </TableCell>
                        <TableCell className="px-4">
                          {aula.titulo && (
                            <div className="line-clamp-1 font-medium">
                              {aula.titulo}
                            </div>
                          )}
                          {aula.tem_sumario ? (
                            <div className="line-clamp-1 text-xs text-muted-foreground">
                              {aula.sumario}
                            </div>
                          ) : aula.estado !== 'cancelado' ? (
                            <div className="text-xs italic text-muted-foreground">
                              Sem sumário escrito
                            </div>
                          ) : null}
                        </TableCell>
                        <TableCell className="px-4 text-center">
                          {aula.estado === 'publicado' ? (
                            <Badge className="bg-green-600 text-[10px] hover:bg-green-600">
                              ✓ {aula.estado_label}
                            </Badge>
                          ) : aula.estado === 'cancelado' ? (
                            <Badge variant="destructive" className="text-[10px]">
                              {aula.estado_label}
                            </Badge>
                          ) : (
                            <Badge variant="secondary" className="text-[10px]">
                              {aula.estado_label}
                            </Badge>
                          )}
                        </TableCell>
                        <TableCell className="px-4 text-center">
                          <Button
                            variant="ghost"
                            size="icon"
                            asChild
                            className="hover:cursor-pointer"
                          >
                            <Link href={aula.urls.ver}>
                              <Eye className="size-4" />
                            </Link>
                          </Button>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
            )}

            {aulas.last_page > 1 && aulasFiltradas.length > 0 && (
              <div className="flex items-center justify-between border-t px-4 py-3">
                <p className="text-xs text-muted-foreground">
                  Página {aulas.current_page} de {aulas.last_page} ·{' '}
                  {aulas.total} no total
                </p>
                <div className="flex gap-2">
                  <Button
                    variant="outline"
                    size="sm"
                    disabled={aulas.current_page === 1}
                    onClick={() =>
                      router.get(
                        '/dashboard/aulas',
                        {
                          ...filtrosLocais,
                          page: aulas.current_page - 1,
                        },
                        { preserveScroll: true, preserveState: true },
                      )
                    }
                    className="hover:cursor-pointer"
                  >
                    <ChevronLeft className="size-4" />
                    Anterior
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    disabled={aulas.current_page === aulas.last_page}
                    onClick={() =>
                      router.get(
                        '/dashboard/aulas',
                        {
                          ...filtrosLocais,
                          page: aulas.current_page + 1,
                        },
                        { preserveScroll: true, preserveState: true },
                      )
                    }
                    className="hover:cursor-pointer"
                  >
                    Próxima
                    <ChevronRight className="size-4" />
                  </Button>
                </div>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </>
  );
}