import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import {
  FileText,
  Plus,
  Search,
  Filter,
  Download,
  Eye,
  Calendar,
  BookOpen,
  GraduationCap,
  Clock,
  CheckCircle2,
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
  show as planificacoesShow,
} from '@/actions/App/Http/Controllers/Tenant/PlanificacaoController';

export default function Index({
  planificacoes,
  filters = {},
  disciplinas = [],
  classes = [],
  anosLectivos = [],
  anoAtivoId,
  periodos = [],
  can = {},
}) {
  const [filtros, setFiltros] = useState({
    ano_letivo_id: filters.ano_letivo_id ?? anoAtivoId ?? '',
    disciplina_id: filters.disciplina_id ?? '',
    classe_id: filters.classe_id ?? '',
    periodo: filters.periodo ?? '',
  });

  const items = planificacoes?.data ?? [];

  const aplicarFiltros = () => {
    router.get(planificacoesIndex().url, filtros, {
      preserveScroll: true,
      preserveState: true,
    });
  };

  const limparFiltros = () => {
    setFiltros({
      ano_letivo_id: anoAtivoId ?? '',
      disciplina_id: '',
      classe_id: '',
      periodo: '',
    });
    router.get(planificacoesIndex().url, {}, { preserveScroll: true });
  };

  return (
    <>
      <Head title="Planificações" />

      <div className="mx-auto w-full max-w-7xl space-y-6 p-4 sm:p-6">
        {/* Cabeçalho */}
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">Planificações</h1>
            <p className="mt-1 text-sm text-muted-foreground">
              Consulte as planificações das disciplinas por classe e período.
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
        <Card>
          <CardContent className="grid grid-cols-1 gap-3 pt-6 sm:grid-cols-2 lg:grid-cols-5">
            <Select
              value={filtros.ano_letivo_id}
              onValueChange={(v) =>
                setFiltros((prev) => ({ ...prev, ano_letivo_id: v }))
              }
            >
              <SelectTrigger>
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
              value={filtros.disciplina_id}
              onValueChange={(v) =>
                setFiltros((prev) => ({ ...prev, disciplina_id: v }))
              }
            >
              <SelectTrigger>
                <SelectValue placeholder="Disciplina" />
              </SelectTrigger>
              <SelectContent>
                {disciplinas.map((d) => (
                  <SelectItem key={d.id} value={d.id}>
                    {d.nome}
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
                Filtrar
              </Button>
              <Button variant="outline" onClick={limparFiltros}>
                Limpar
              </Button>
            </div>
          </CardContent>
        </Card>

        {/* Lista */}
        {items.length === 0 ? (
          <Empty>
            <EmptyHeader>
              <EmptyMedia variant="icon">
                <FileText />
              </EmptyMedia>
              <EmptyTitle>Sem planificações</EmptyTitle>
              <EmptyDescription>
                {can.create
                  ? 'Ainda não foi criada nenhuma planificação para os filtros atuais.'
                  : 'Ainda não há planificações disponíveis para si.'}
              </EmptyDescription>
            </EmptyHeader>
          </Empty>
        ) : (
          <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            {items.map((p) => (
              <Card key={p.id} className="flex flex-col hover:shadow-md transition-shadow">
                <CardHeader className="pb-3">
                  <div className="flex items-start justify-between gap-2">
                    <Badge variant="outline" className="text-[10px]">
                      v{p.versao}
                    </Badge>
                    <Badge variant="secondary" className="text-[10px]">
                      {p.periodo}
                    </Badge>
                  </div>
                  <CardTitle className="mt-2 text-base leading-tight">
                    {p.titulo}
                  </CardTitle>
                  <CardDescription className="flex items-center gap-1 text-xs">
                    <Clock className="size-3" />
                    {p.atualizada_em}
                  </CardDescription>
                </CardHeader>

                <CardContent className="flex-1 space-y-2 text-sm">
                  <p className="flex items-center gap-2">
                    <BookOpen className="size-4 text-muted-foreground" />
                    <strong>{p.disciplina?.nome ?? '—'}</strong>
                  </p>

                  <p className="flex items-center gap-2 text-muted-foreground">
                    <GraduationCap className="size-4" />
                    {p.classe?.nome ?? '—'}
                  </p>

                  <p className="flex items-center gap-2 text-muted-foreground">
                    <Calendar className="size-4" />
                    {p.ano_letivo ?? '—'}
                  </p>
                </CardContent>

                <div className="border-t p-3">
                  <Button variant="outline" asChild className="w-full">
                    <Link href={planificacoesShow(p.id).url}>
                      <Eye className="mr-1.5 size-4" />
                      Ver detalhes
                    </Link>
                  </Button>
                </div>
              </Card>
            ))}
          </div>
        )}

        {/* Paginação */}
        {planificacoes?.links && planificacoes.links.length > 3 && (
          <div className="flex flex-wrap justify-center gap-1">
            {planificacoes.links.map((link, i) => (
              <Link
                key={i}
                href={link.url || '#'}
                className={`rounded px-3 py-1 text-sm ${
                  link.active
                    ? 'bg-primary text-primary-foreground'
                    : 'hover:bg-muted'
                } ${!link.url ? 'pointer-events-none opacity-50' : ''}`}
                dangerouslySetInnerHTML={{ __html: link.label }}
                preserveScroll
              />
            ))}
          </div>
        )}
      </div>
    </>
  );
}