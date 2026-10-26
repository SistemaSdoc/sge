import { Head, Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import {
  Calendar,
  ChevronLeft,
  ChevronRight,
  Clock,
  Lock,
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

// ─────────────────────────────────────────────────────────
// Helper — partes da data em PT-BR
// ─────────────────────────────────────────────────────────
function partesData(iso: string): {
  diaSemana: string;
  dia: string;
  mes: string;
  mesCompleto: string;
  ano: string;
} {
  const d = new Date(iso + 'T12:00:00');
  if (Number.isNaN(d.getTime())) {
    return {
      diaSemana: '—',
      dia: '—',
      mes: '—',
      mesCompleto: '—',
      ano: '—',
    };
  }
  return {
    diaSemana: d.toLocaleDateString('pt-BR', { weekday: 'long' }),
    dia: String(d.getDate()).padStart(2, '0'),
    mes: d
      .toLocaleDateString('pt-BR', { month: 'short' })
      .replace('.', '')
      .toUpperCase(),
    mesCompleto: d.toLocaleDateString('pt-BR', { month: 'long' }),
    ano: String(d.getFullYear()),
  };
}

function formatarDataHumana(iso: string): string {
  const p = partesData(iso);
  return `${p.diaSemana}, ${p.dia} de ${p.mesCompleto} de ${p.ano}`;
}

// ─────────────────────────────────────────────────────────
// Componente principal
// ─────────────────────────────────────────────────────────
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

  // Estatísticas
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
  const comSumario = totalAulas - semSumario;

  return (
    <>
      <Head title="As minhas aulas" />

      <div className="mx-auto w-full max-w-6xl space-y-4 p-4 sm:p-6">
        {/* ── Card 1: Header ───────────────────────────── */}
        <Card className="gap-0 pb-0">
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
                  Escreva o sumário depois de cada aula. Publique para os
                  alunos verem.
                </CardDescription>
              </div>

              <Button asChild className="hover:cursor-pointer">
                <Link href="/dashboard/professor/aulas/create">
                  Aula extra
                </Link>
              </Button>
            </div>
          </CardHeader>
        </Card>

        {/* ── Card 2: Stats + Busca + Navegação ────────── */}
        <Card className="gap-0 pb-0">
          <CardContent className="space-y-3 p-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
              <div className="flex flex-wrap items-center gap-3 text-xs">
                <span className="inline-flex items-center gap-1.5">
                  <span className="size-1.5 rounded-full bg-foreground" />
                  <span className="font-semibold tabular-nums">
                    {totalAulas}
                  </span>
                  <span className="text-muted-foreground">
                    {totalAulas === 1 ? 'aula' : 'aulas'}
                  </span>
                </span>
                <span className="inline-flex items-center gap-1.5">
                  <span className="size-1.5 rounded-full bg-foreground/60" />
                  <span className="font-semibold tabular-nums">
                    {comSumario}
                  </span>
                  <span className="text-muted-foreground">com sumário</span>
                </span>
                {semSumario > 0 && (
                  <span className="inline-flex items-center gap-1.5">
                    <span className="size-1.5 rounded-full border border-foreground" />
                    <span className="font-semibold tabular-nums">
                      {semSumario}
                    </span>
                    <span className="text-muted-foreground">sem sumário</span>
                  </span>
                )}
              </div>

              <div className="flex flex-wrap items-center gap-1">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => navegarDia(-7)}
                  className="h-8 hover:cursor-pointer"
                  aria-label="Semana anterior"
                >
                  <ChevronLeft className="size-4" />
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={irParaHoje}
                  className="h-8 hover:cursor-pointer"
                >
                  Hoje
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => navegarDia(7)}
                  className="h-8 hover:cursor-pointer"
                  aria-label="Próxima semana"
                >
                  <ChevronRight className="size-4" />
                </Button>
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
          </CardContent>
        </Card>

        {/* ── Agenda por dia ───────────────────────────── */}
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
          <div className="space-y-3">
            {filtrosAplicados.map((dia) => {
              const p = partesData(dia.data);

              return (
                <Card
                  key={dia.data}
                  className={cn(
                    'gap-0 overflow-hidden pb-0',
                    dia.is_passado && 'opacity-70',
                  )}
                >
                  {/* Day header */}
                  <div
                    className={cn(
                      'flex items-center gap-3 border-b px-4 py-3',
                      dia.is_hoje ? 'bg-muted/50' : 'bg-transparent',
                    )}
                  >
                    {/* Bloco do dia */}
                    <div
                      className={cn(
                        'flex size-12 shrink-0 flex-col items-center justify-center border',
                        dia.is_hoje
                          ? 'border-foreground bg-foreground text-background'
                          : 'border-border bg-background text-foreground',
                      )}
                    >
                      <span className="text-lg font-bold leading-none tabular-nums">
                        {p.dia}
                      </span>
                      <span className="mt-0.5 text-[9px] font-medium uppercase leading-none tracking-wide">
                        {p.mes}
                      </span>
                    </div>

                    {/* Info */}
                    <div className="min-w-0 flex-1">
                      <div className="flex flex-wrap items-center gap-2">
                        <h2 className="text-sm font-semibold capitalize leading-tight">
                          {p.diaSemana}
                        </h2>
                        {dia.is_hoje && (
                          <Badge
                            variant="secondary"
                            className="text-[10px] uppercase tracking-wide"
                          >
                            Hoje
                          </Badge>
                        )}
                        {dia.is_passado && !dia.is_hoje && (
                          <span className="text-[10px] uppercase tracking-wide text-muted-foreground">
                            Passado
                          </span>
                        )}
                      </div>
                      <p className="mt-0.5 text-xs text-muted-foreground">
                        {dia.aulas.length}{' '}
                        {dia.aulas.length === 1 ? 'aula' : 'aulas'}
                      </p>
                    </div>
                  </div>

                  {/* Cards horizontais */}
                  <div className="divide-y">
                    {dia.aulas.map((item, idx) => (
                      <CardAulaRow
                        key={item.slot.id + idx}
                        item={item}
                        isPassado={dia.is_passado}
                        data={dia.data}
                      />
                    ))}
                  </div>
                </Card>
              );
            })}
          </div>
        )}

        {/* ── Aulas extra / reposições ─────────────────── */}
        {extras.length > 0 && (
          <Card className="gap-0 pb-0">
            <CardHeader className="border-b px-5 py-3 sm:px-6">
              <div className="flex items-center justify-between gap-2">
                <div>
                  <CardTitle className="text-sm">Aulas extra</CardTitle>
                  <CardDescription className="mt-0.5 text-xs">
                    Aulas extra
                  </CardDescription>
                </div>
               
              </div>
            </CardHeader>

            <CardContent className="divide-y p-0!">
              {extras.map((aula) => {
                const p = partesData(aula.data);
                return (
                  <div
                    key={aula.id}
                    className="flex flex-col gap-2 px-4 py-3 transition-colors hover:bg-muted/30 sm:flex-row sm:items-center"
                  >
                    <div className="flex min-w-0 flex-1 items-center gap-3">
                      <span className="shrink-0 text-[11px] font-semibold tabular-nums">
                        {p.dia}/{p.mes}
                      </span>
                      <span className="shrink-0 text-[10px] text-muted-foreground">
                        {aula.hora_inicio} – {aula.hora_fim}
                      </span>

                      <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-semibold leading-tight">
                          {aula.disciplina_nome}
                        </p>
                        <p className="mt-0.5 truncate text-xs text-muted-foreground">
                          {aula.turma_nome}
                        </p>
                      </div>

                      <Badge variant="outline" className="shrink-0 text-[10px]">
                        {aula.estado_label}
                      </Badge>
                    </div>

                    <div className="shrink-0 sm:ml-2">
                      <Button
                        asChild
                        variant="outline"
                        size="sm"
                        className="h-7 text-xs hover:cursor-pointer"
                      >
                        <Link
                          href={`/dashboard/professor/aulas/${aula.id}/edit`}
                        >
                          Editar
                        </Link>
                      </Button>
                    </div>
                  </div>
                );
              })}
            </CardContent>
          </Card>
        )}
      </div>
    </>
  );
}

// ═══════════════════════════════════════════════════════════
// Card horizontal (linha da agenda)
// ═══════════════════════════════════════════════════════════
function CardAulaRow({
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
        'group flex gap-3 px-4 py-3 transition-colors hover:bg-muted/30',
        cancelado && 'opacity-60',
      )}
    >
      {/* Coluna da hora */}
      <div className="flex w-14 shrink-0 flex-col items-end pt-0.5">
        <span className="text-sm font-semibold tabular-nums leading-tight">
          {slot.hora_inicio}
        </span>
        <span className="text-[10px] text-muted-foreground tabular-nums leading-tight">
          {slot.hora_fim}
        </span>
        <div className="mt-2 h-px w-full bg-border" aria-hidden />
      </div>

      {/* Conteúdo */}
      <div className="min-w-0 flex-1">
        <div className="flex items-start justify-between gap-2">
          <div className="min-w-0">
            <h3 className="truncate text-sm font-semibold leading-tight">
              {slot.disciplina_nome}
            </h3>
            <p className="mt-0.5 truncate text-xs text-muted-foreground">
              {slot.turma_nome}
            </p>
          </div>

          <div className="shrink-0">
            {semRegisto && !isPassado && (
              <Badge variant="outline" className="text-[10px]">
                Pendente
              </Badge>
            )}
            {semRegisto && isPassado && (
              <Badge variant="outline" className="text-[10px]">
                Sem sumário
              </Badge>
            )}
            {rascunho && (
              <Badge variant="secondary" className="text-[10px]">
                Rascunho
              </Badge>
            )}
            {publicado && (
              <Badge variant="secondary" className="text-[10px]">
                ✓ Publicado
              </Badge>
            )}
            {cancelado && (
              <Badge variant="outline" className="text-[10px]">
                Cancelado
              </Badge>
            )}
          </div>
        </div>

        {aula?.titulo && (
          <p className="mt-2 text-xs font-medium leading-snug">
            {aula.titulo}
          </p>
        )}
        {aula?.tem_sumario ? (
          <p className="mt-1 line-clamp-2 text-xs text-muted-foreground">
            {aula.sumario}
          </p>
        ) : (
          <p className="mt-1 text-xs italic text-muted-foreground">
            {isPassado ? 'Sumário não foi escrito.' : 'Sumário a escrever.'}
          </p>
        )}

        <div className="mt-2 flex flex-wrap items-center justify-between gap-2">
          <div className="flex flex-wrap items-center gap-1.5">
            {publicado && aula?.horas_restantes_edicao !== null && (
              <span className="inline-flex items-center gap-1 bg-muted/60 px-1.5 py-0.5 text-[10px] text-muted-foreground">
                <Clock className="size-3" />
                {aula.horas_restantes_edicao}h para editar
              </span>
            )}
            {publicado && aula?.esta_editavel === false && (
              <span className="inline-flex items-center gap-1 bg-muted px-1.5 py-0.5 text-[10px] text-muted-foreground">
                <Lock className="size-3" />
                Bloqueado
              </span>
            )}
          </div>

          <div className="flex shrink-0 items-center gap-1">
            {semRegisto ? (
              <Button
                asChild
                variant={isPassado ? 'outline' : 'default'}
                size="sm"
                className="h-7 text-xs hover:cursor-pointer"
              >
                <Link
                  href={`/dashboard/professor/aulas/create?slot_id=${slot.id}&data=${data}`}
                >
                  Escrever
                </Link>
              </Button>
            ) : (
              <>
                {aula?.esta_editavel ? (
                  <Button
                    asChild
                    variant="outline"
                    size="sm"
                    className="h-7 text-xs hover:cursor-pointer"
                  >
                    <Link href={`/dashboard/professor/aulas/${aula.id}/edit`}>
                      Editar
                    </Link>
                  </Button>
                ) : (
                  <Button
                    variant="outline"
                    size="sm"
                    className="h-7 text-xs"
                    disabled
                  >
                    <Lock className="mr-1 size-3" />
                    Bloqueado
                  </Button>
                )}
                {aula && (
                  <Button
                        asChild
                    variant="outline"
                    size="sm"
                    className="h-7 text-xs hover:cursor-pointer"
                  >
                    <Link href={`/dashboard/aulas/${aula.id}`}>
                      Visualizar
                    </Link>
                  </Button>
                )}
              </>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}