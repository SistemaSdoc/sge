import { Head, Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import {
  Calendar,
  ChevronLeft,
  ChevronRight,
  Clock,
  Edit3,
  Eye,
  FileText,
  GraduationCap,
  Lock,
  Plus,
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
import { EmptyState } from '@/components/empty-state';
import { cn } from '@/lib/utils';

interface Slot {
  id: string;
  dia_semana: number;
  hora_inicio: string;
  hora_fim: string;
  turma_id: string;
  turma_nome: string;
  disciplina_id: string;
  disciplina_nome: string;
  disciplina_sigla?: string;
}

interface Aula {
  id: string;
  data: string;
  data_humana: string;
  hora_inicio: string;
  hora_fim: string;
  turma_nome: string;
  disciplina_nome: string;
  titulo: string | null;
  sumario: string | null;
  tpc: string | null;
  estado: 'rascunho' | 'publicado' | 'cancelado';
  estado_label: string;
  publicado_em: string | null;
  esta_editavel: boolean;
  horas_restantes_edicao: number | null;
  tem_sumario: boolean;
}

interface ItemAgenda {
  slot: Slot;
  aula: Aula | null;
  estado: string;
}

interface DiaAgenda {
  data: string;
  data_humana: string;
  dia_semana: number;
  is_hoje: boolean;
  is_passado: boolean;
  aulas: ItemAgenda[];
}

interface Props {
  agenda: DiaAgenda[];
  extras: Aula[];
  filtros: { de: string; ate: string };
}

export default function Index({ agenda = [], extras = [], filtros }: Props) {
  const [busca, setBusca] = useState('');

  const filtrosAplicados = useMemo(() => {
    const termo = busca.trim().toLowerCase();
    if (!termo) return agenda;

    return agenda
      .map((dia) => ({
        ...dia,
        aulas: dia.aulas.filter(
          (item) =>
            (item.slot.disciplina_nome ?? '').toLowerCase().includes(termo) ||
            (item.slot.turma_nome ?? '').toLowerCase().includes(termo) ||
            (item.aula?.titulo ?? '').toLowerCase().includes(termo) ||
            (item.aula?.sumario ?? '').toLowerCase().includes(termo),
        ),
      }))
      .filter((dia) => dia.aulas.length > 0);
  }, [agenda, busca]);

  const navegarDia = (offsetDias: number) => {
    const de = new Date(filtros.de);
    const ate = new Date(filtros.ate);
    de.setDate(de.getDate() + offsetDias);
    ate.setDate(ate.getDate() + offsetDias);

    router.get(
      '/dashboard/professor/aulas',
      {
        de: de.toISOString().slice(0, 10),
        ate: ate.toISOString().slice(0, 10),
      },
      { preserveScroll: true, preserveState: true },
    );
  };

  const irParaHoje = () => {
    const hoje = new Date().toISOString().slice(0, 10);
    const ate = new Date();
    ate.setDate(ate.getDate() + 7);

    router.get(
      '/dashboard/professor/aulas',
      { de: hoje, ate: ate.toISOString().slice(0, 10) },
      { preserveScroll: true, preserveState: true },
    );
  };

  const totalAulas = agenda.reduce((acc, d) => acc + d.aulas.length, 0);
  const semSumario = agenda.reduce(
    (acc, d) =>
      acc +
      d.aulas.filter(
        (a) =>
          (a.estado === 'sem_registo' || !a.aula?.tem_sumario) &&
          !d.is_passado &&
          a.aula?.estado !== 'cancelado',
      ).length,
    0,
  );

  return (
    <>
      <Head title="As minhas aulas" />

      <div className="mx-auto w-full max-w-7xl space-y-4 p-4 sm:p-6">
        {/* Header + Filtros */}
        <Card className="gap-0! overflow-visible pb-0">
          <CardHeader className="border-b border-foreground/10">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
              <div className="min-w-0 space-y-1">
                <Breadcrumb>
                  <BreadcrumbList className="flex-wrap">
                    <BreadcrumbItem className="min-w-0">
                      <BreadcrumbLink asChild>
                        <span className="line-clamp-2 text-sm font-semibold text-primary">
                          Professor
                        </span>
                      </BreadcrumbLink>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem className="min-w-0">
                      <BreadcrumbPage className="line-clamp-2 text-sm font-semibold text-secondary">
                        As minhas aulas
                      </BreadcrumbPage>
                    </BreadcrumbItem>
                  </BreadcrumbList>
                </Breadcrumb>
                <CardDescription>
                  Escreve o sumário depois de cada aula. Publica para os alunos
                  verem.
                </CardDescription>
              </div>

              <Button asChild className="hover:cursor-pointer">
                <Link href="/dashboard/professor/aulas/create">
                  <Plus className="mr-1.5 size-4" />
                  Aula extra
                </Link>
              </Button>
            </div>
          </CardHeader>

          <div className="space-y-3 px-4 py-3">
            <div className="flex flex-wrap items-center justify-between gap-2">
              <h2 className="text-sm font-semibold whitespace-nowrap">
                Filtros
              </h2>
              <div className="flex flex-wrap gap-2">
                <Badge variant="secondary" className="text-[10px]">
                  {totalAulas} {totalAulas === 1 ? 'aula' : 'aulas'} no período
                </Badge>
                {semSumario > 0 && (
                  <Badge variant="outline" className="text-[10px]">
                    {semSumario} sem sumário
                  </Badge>
                )}
              </div>
            </div>

            <div className="relative">
              <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                value={busca}
                onChange={(e) => setBusca(e.target.value)}
                placeholder="Buscar por disciplina, turma, título ou sumário…"
                className="pl-9"
              />
            </div>

            <div className="flex flex-wrap items-center gap-2">
              <Button
                variant="outline"
                size="sm"
                onClick={() => navegarDia(-7)}
                className="hover:cursor-pointer"
              >
                <ChevronLeft className="size-4" />
                Semana anterior
              </Button>
              <Button
                variant="outline"
                size="sm"
                onClick={irParaHoje}
                className="hover:cursor-pointer"
              >
                Hoje
              </Button>
              <Button
                variant="outline"
                size="sm"
                onClick={() => navegarDia(7)}
                className="hover:cursor-pointer"
              >
                Próxima semana
                <ChevronRight className="size-4" />
              </Button>
            </div>
          </div>
        </Card>

        {/* Agenda por dia */}
        {filtrosAplicados.length === 0 ? (
          <Card className="gap-0 pb-0">
            <CardContent className="p-0!">
              <EmptyState
                variant="table"
                icon={Calendar}
                title={busca ? 'Sem resultados' : 'Sem aulas no período'}
                description={
                  busca
                    ? 'Nenhuma aula corresponde à pesquisa.'
                    : 'Não há aulas agendadas para o intervalo selecionado.'
                }
              />
            </CardContent>
          </Card>
        ) : (
          <div className="space-y-4">
            {filtrosAplicados.map((dia) => (
              <Card key={dia.data} className="gap-0 pb-0">
                <CardHeader className="border-b">
                  <div className="flex items-center gap-3">
                    <div
                      className={cn(
                        'flex size-9 shrink-0 items-center justify-center',
                        dia.is_hoje
                          ? 'bg-primary text-primary-foreground'
                          : dia.is_passado
                            ? 'bg-muted text-muted-foreground'
                            : 'bg-primary/10 text-primary',
                      )}
                    >
                      <Calendar className="size-4" />
                    </div>
                    <div className="flex-1">
                      <h2
                        className={cn(
                          'text-base font-semibold capitalize leading-none',
                          dia.is_hoje ? 'text-primary' : '',
                        )}
                      >
                        {dia.data_humana}
                      </h2>
                      <p className="mt-1 text-xs text-muted-foreground">
                        {dia.aulas.length}{' '}
                        {dia.aulas.length === 1 ? 'aula' : 'aulas'}
                      </p>
                    </div>
                    {dia.is_hoje && (
                      <Badge variant="default" className="text-[10px]">
                        Hoje
                      </Badge>
                    )}
                  </div>
                </CardHeader>

                <CardContent className="grid grid-cols-1 gap-2 p-3 md:grid-cols-2">
                  {dia.aulas.map((item, idx) => (
                    <CardAula
                      key={item.slot.id + idx}
                      item={item}
                      isPassado={dia.is_passado}
                      data={dia.data}
                    />
                  ))}
                </CardContent>
              </Card>
            ))}
          </div>
        )}

        {/* Aulas extra */}
        {extras.length > 0 && (
          <Card className="gap-0 pb-0">
            <CardHeader className="border-b">
              <div className="flex items-center gap-3">
                <div className="flex size-9 items-center justify-center bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400">
                  <FileText className="size-4" />
                </div>
                <div>
                  <CardTitle className="text-base leading-none">
                    Aulas extra / reposições
                  </CardTitle>
                  <CardDescription className="mt-1 text-xs">
                    {extras.length} fora do horário
                  </CardDescription>
                </div>
              </div>
            </CardHeader>

            <CardContent className="grid grid-cols-1 gap-2 p-3 md:grid-cols-2">
              {extras.map((aula) => (
                <div
                  key={aula.id}
                  className="flex flex-col border bg-background transition-colors hover:border-primary/30"
                >
                  <div className="border-b bg-muted/30 px-3 py-2">
                    <div className="flex flex-wrap items-center gap-2">
                      <Badge variant="outline" className="text-[10px]">
                        {aula.data_humana}
                      </Badge>
                      <Badge variant="secondary" className="text-[10px]">
                        {aula.estado_label}
                      </Badge>
                    </div>
                    <p className="mt-1 text-sm font-semibold leading-tight">
                      {aula.disciplina_nome} · {aula.turma_nome}
                    </p>
                    <p className="text-xs text-muted-foreground">
                      {aula.hora_inicio} – {aula.hora_fim}
                    </p>
                  </div>
                  <div className="flex-1 px-3 py-2">
                    {aula.tem_sumario ? (
                      <p className="line-clamp-2 text-sm text-muted-foreground">
                        {aula.sumario}
                      </p>
                    ) : (
                      <p className="text-sm italic text-muted-foreground">
                        Sem sumário escrito
                      </p>
                    )}
                  </div>
                  <div className="border-t px-3 py-2">
                    <Button asChild variant="outline" size="sm" className="w-full">
                      <Link href={`/dashboard/professor/aulas/${aula.id}/edit`}>
                        <Edit3 className="mr-1.5 size-4" />
                        Editar
                      </Link>
                    </Button>
                  </div>
                </div>
              ))}
            </CardContent>
          </Card>
        )}
      </div>
    </>
  );
}

// ═══════════════════════════════════════════════════════════
// Card de aula
// ═══════════════════════════════════════════════════════════

function CardAula({
  item,
  isPassado,
  data,
}: {
  item: ItemAgenda;
  isPassado: boolean;
  data: string;
}) {
  const { slot, aula, estado } = item;

  const semRegisto = estado === 'sem_registo';
  const rascunho = estado === 'rascunho';
  const publicado = estado === 'publicado';
  const cancelado = estado === 'cancelado';

  return (
    <div
      className={cn(
        'flex flex-col border bg-background transition-colors hover:border-primary/30',
        cancelado ? 'opacity-60' : '',
      )}
    >
      <div className="border-b bg-muted/30 px-3 py-2">
        <div className="flex items-start justify-between gap-2">
          <div className="flex min-w-0 items-center gap-2">
            <div className="flex size-8 shrink-0 items-center justify-center bg-primary/10">
              <GraduationCap className="size-4 text-primary" />
            </div>
            <div className="min-w-0">
              <p className="truncate text-sm font-semibold leading-tight">
                {slot.disciplina_nome}
              </p>
              <p className="mt-0.5 truncate text-xs text-muted-foreground">
                {slot.turma_nome}
              </p>
            </div>
          </div>

          {semRegisto && !isPassado && (
            <Badge variant="outline" className="shrink-0 text-[10px]">
              Pendente
            </Badge>
          )}
          {semRegisto && isPassado && (
            <Badge variant="outline" className="shrink-0 text-[10px]">
              Sem sumário
            </Badge>
          )}
          {rascunho && (
            <Badge variant="secondary" className="shrink-0 text-[10px]">
              Rascunho
            </Badge>
          )}
          {publicado && (
            <Badge className="shrink-0 bg-green-600 text-[10px] hover:bg-green-600">
              ✓ Publicado
            </Badge>
          )}
          {cancelado && (
            <Badge variant="destructive" className="shrink-0 text-[10px]">
              Cancelado
            </Badge>
          )}
        </div>

        <div className="mt-1.5 flex items-center gap-1.5 text-xs text-muted-foreground">
          <Clock className="size-3" />
          <span>
            {slot.hora_inicio} – {slot.hora_fim}
          </span>
        </div>
      </div>

      <div className="flex-1 px-3 py-2">
        {aula?.titulo && (
          <p className="text-sm font-medium leading-tight">{aula.titulo}</p>
        )}
        {aula?.tem_sumario ? (
          <p className="mt-1 line-clamp-3 text-sm text-muted-foreground">
            {aula.sumario}
          </p>
        ) : (
          <p className="text-sm italic text-muted-foreground">
            {isPassado ? 'Sumário não foi escrito.' : 'Sumário por escrever.'}
          </p>
        )}

        {publicado && aula?.horas_restantes_edicao !== null && (
          <div className="mt-2 inline-flex items-center gap-1 bg-primary/5 px-2 py-1 text-[10px] text-primary">
            <Clock className="size-3" />
            {aula.horas_restantes_edicao}h para editar
          </div>
        )}

        {publicado && aula?.esta_editavel === false && (
          <div className="mt-2 inline-flex items-center gap-1 bg-muted px-2 py-1 text-[10px] text-muted-foreground">
            <Lock className="size-3" />
            Bloqueado para edição
          </div>
        )}
      </div>

      <div className="border-t px-3 py-2">
        {semRegisto ? (
          <Button
            asChild
            variant={isPassado ? 'outline' : 'default'}
            size="sm"
            className="w-full hover:cursor-pointer"
          >
            <Link
              href={`/dashboard/professor/aulas/create?slot_id=${slot.id}&data=${data}`}
            >
              <Plus className="mr-1.5 size-4" />
              Escrever sumário
            </Link>
          </Button>
        ) : (
          <div className="flex gap-2">
            {aula?.esta_editavel ? (
              <Button
                asChild
                variant="outline"
                size="sm"
                className="flex-1 hover:cursor-pointer"
              >
                <Link href={`/dashboard/professor/aulas/${aula.id}/edit`}>
                  <Edit3 className="mr-1.5 size-4" />
                  Editar
                </Link>
              </Button>
            ) : (
              <Button variant="outline" size="sm" className="flex-1" disabled>
                <Lock className="mr-1.5 size-4" />
                Bloqueado
              </Button>
            )}
            {aula && (
              <Button
                asChild
                variant="ghost"
                size="icon"
                className="hover:cursor-pointer"
              >
                <Link href={`/dashboard/aulas/${aula.id}`}>
                  <Eye className="size-4" />
                </Link>
              </Button>
            )}
          </div>
        )}
      </div>
    </div>
  );
}