import { Head, Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import {
  BookOpen,
  Calendar,
  ChevronLeft,
  ChevronRight,
  Clock,
  Eye,
  FileText,
  GraduationCap,
  NotebookPen,
  Search,
  XCircle,
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
  conteudo_previsto: string | null;
  sumario: string | null;
  tpc: string | null;
  estado: 'rascunho' | 'publicado' | 'cancelado';
  estado_label: string;
  tem_sumario: boolean;
  urls?: { ver?: string };
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
  filtros: { de: string; ate: string };
}

// ═══════════════════════════════════════════════════════════
// Helpers — semanas
// ═══════════════════════════════════════════════════════════

function segundaDaSemana(dataStr: string): Date {
  const d = new Date(dataStr + 'T12:00:00');
  const day = d.getDay();
  const diff = day === 0 ? -6 : 1 - day;
  const monday = new Date(d);
  monday.setDate(monday.getDate() + diff);
  return monday;
}

function toISODate(d: Date): string {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

function labelSemanaCurta(de: Date): string {
  const ate = new Date(de);
  ate.setDate(ate.getDate() + 6);

  const mesmoMes = de.getMonth() === ate.getMonth();

  if (mesmoMes) {
    const mes = ate.toLocaleDateString('pt-PT', { month: 'short' });
    return `${de.getDate()}–${ate.getDate()} ${mes}`;
  }

  const deLabel = de.toLocaleDateString('pt-PT', {
    day: 'numeric',
    month: 'short',
  });
  const ateLabel = ate.toLocaleDateString('pt-PT', {
    day: 'numeric',
    month: 'short',
  });
  return `${deLabel}–${ateLabel}`;
}

function labelSemanaCompleta(de: Date): string {
  const ate = new Date(de);
  ate.setDate(ate.getDate() + 6);

  const mesmoMes = de.getMonth() === ate.getMonth();
  const ano = ate.toLocaleDateString('pt-PT', { year: 'numeric' });

  if (mesmoMes) {
    const mes = ate.toLocaleDateString('pt-PT', { month: 'long' });
    return `${de.getDate()} a ${ate.getDate()} de ${mes} de ${ano}`;
  }

  const deLabel = de.toLocaleDateString('pt-PT', {
    day: 'numeric',
    month: 'short',
  });
  const ateLabel = ate.toLocaleDateString('pt-PT', {
    day: 'numeric',
    month: 'short',
  });
  return `${deLabel} a ${ateLabel} de ${ano}`;
}

// ═══════════════════════════════════════════════════════════
// Componente
// ═══════════════════════════════════════════════════════════

export default function Index({ agenda = [], filtros }: Props) {
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

  const navegar = (dias: number) => {
    const de = new Date(filtros.de);
    const ate = new Date(filtros.ate);
    de.setDate(de.getDate() + dias);
    ate.setDate(ate.getDate() + dias);

    router.get(
      '/dashboard/aulas',
      { de: toISODate(de), ate: toISODate(ate) },
      { preserveScroll: true, preserveState: true },
    );
  };

  const irParaSemana = (de: string, ate: string) => {
    router.get(
      '/dashboard/aulas',
      { de, ate },
      { preserveScroll: true, preserveState: true },
    );
  };

  const irParaHoje = () => {
    const hoje = new Date();
    const segunda = segundaDaSemana(toISODate(hoje));
    const domingo = new Date(segunda);
    domingo.setDate(domingo.getDate() + 6);
    irParaSemana(toISODate(segunda), toISODate(domingo));
  };

  const semanasPag = useMemo(() => {
    const hojeISO = toISODate(new Date());
    const referencia =
      hojeISO >= filtros.de && hojeISO <= filtros.ate ? hojeISO : filtros.de;

    const segundaReferencia = segundaDaSemana(referencia);
    const SLOTS = 5;
    const METADE = Math.floor(SLOTS / 2);

    const lista: {
      de: string;
      ate: string;
      label: string;
      isAtual: boolean;
      segunda: Date;
    }[] = [];

    for (let i = -METADE; i <= METADE; i++) {
      const segunda = new Date(segundaReferencia);
      segunda.setDate(segunda.getDate() + i * 7);
      const domingo = new Date(segunda);
      domingo.setDate(domingo.getDate() + 6);

      const deFiltro = new Date(filtros.de + 'T12:00:00');
      const isAtual = deFiltro >= segunda && deFiltro <= domingo;

      lista.push({
        de: toISODate(segunda),
        ate: toISODate(domingo),
        label: labelSemanaCurta(segunda),
        isAtual,
        segunda,
      });
    }
    return lista;
  }, [filtros.de, filtros.ate]);

  const semanaAtualLabel = useMemo(
    () => labelSemanaCompleta(segundaDaSemana(filtros.de)),
    [filtros.de],
  );

  const navegarSemanaPag = (direcao: 1 | -1) => {
    const idx = direcao > 0 ? semanasPag.length - 1 : 0;
    const base = new Date(semanasPag[idx].segunda);
    base.setDate(base.getDate() + direcao * 7);
    const fim = new Date(base);
    fim.setDate(fim.getDate() + 6);
    irParaSemana(toISODate(base), toISODate(fim));
  };

  const totalAulas = agenda.reduce((acc, d) => acc + d.aulas.length, 0);

  return (
    <>
      <Head title="Sumários" />

      <div className="mx-auto w-full max-w-5xl space-y-4 p-4 sm:p-6">
        {/* Header + Busca + Semana */}
        <Card className="gap-0! overflow-visible pb-0">
          <CardHeader className="border-b border-foreground/10">
            <div className="min-w-0 space-y-1">
              <Breadcrumb>
                <BreadcrumbList className="flex-wrap">
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbLink asChild>
                      <span className="line-clamp-2 text-sm font-semibold text-primary">
                        Aluno
                      </span>
                    </BreadcrumbLink>
                  </BreadcrumbItem>
                  <BreadcrumbSeparator />
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbPage className="line-clamp-2 text-sm font-semibold text-secondary">
                      Sumários
                    </BreadcrumbPage>
                  </BreadcrumbItem>
                </BreadcrumbList>
              </Breadcrumb>
              <CardDescription>
                Consulta o que foi dado e o que vais dar nas próximas aulas.
              </CardDescription>
            </div>
          </CardHeader>

          <div className="space-y-3 px-4 py-3">
            <div className="flex flex-wrap items-center justify-between gap-2">
              <h2 className="text-sm font-semibold whitespace-nowrap">
                Filtros
              </h2>
              <Badge variant="secondary" className="text-[10px]">
                {totalAulas} {totalAulas === 1 ? 'aula' : 'aulas'} no período
              </Badge>
            </div>

            <div className="relative">
              <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                value={busca}
                onChange={(e) => setBusca(e.target.value)}
                placeholder="Buscar por disciplina, título ou sumário…"
                className="pl-9"
              />
            </div>
          </div>
        </Card>

        {/* Navegação semanal */}
        <Card className="gap-0 pb-0">
          <CardHeader className="flex flex-col gap-3 border-b md:flex-row md:items-center">
            <div className="min-w-0 md:flex-1">
              <CardTitle>Semana</CardTitle>
              <CardDescription className="capitalize">
                {semanaAtualLabel}
              </CardDescription>
            </div>
            <CardAction className="flex flex-wrap items-center gap-1 self-stretch md:w-auto md:self-center">
              <Button
                variant="outline"
                size="sm"
                onClick={() => navegarSemanaPag(-1)}
                className="hover:cursor-pointer"
              >
                <ChevronLeft className="size-4" />
                Anterior
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
                onClick={() => navegarSemanaPag(1)}
                className="hover:cursor-pointer"
              >
                Próxima
                <ChevronRight className="size-4" />
              </Button>
            </CardAction>
          </CardHeader>

          <CardContent className="p-0!">
            <div className="flex flex-wrap items-center gap-1 px-4 py-3">
              {semanasPag.map((s, i) => (
                <button
                  key={i}
                  onClick={() => irParaSemana(s.de, s.ate)}
                  className={cn(
                    'min-w-[68px] border px-2 py-1.5 text-center text-[11px] font-medium transition-all',
                    s.isAtual
                      ? 'border-primary bg-primary text-primary-foreground'
                      : 'border-border bg-background text-muted-foreground hover:border-primary/40 hover:bg-muted hover:text-foreground',
                  )}
                >
                  {s.label}
                </button>
              ))}
            </div>
          </CardContent>
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

                <CardContent className="space-y-2 p-3">
                  {dia.aulas.map((item, idx) => (
                    <CardAulaAluno
                      key={item.slot.id + idx}
                      item={item}
                      isPassado={dia.is_passado}
                    />
                  ))}
                </CardContent>
              </Card>
            ))}
          </div>
        )}
      </div>
    </>
  );
}

// ═══════════════════════════════════════════════════════════
// Card de aula — visão aluno
// ═══════════════════════════════════════════════════════════

function CardAulaAluno({
  item,
  isPassado,
}: {
  item: ItemAgenda;
  isPassado: boolean;
}) {
  const { slot, aula, estado } = item;

  const cancelada = estado === 'cancelado';
  const temSumario = aula?.tem_sumario && aula?.estado === 'publicado';
  const temPrevisto = aula?.conteudo_previsto && aula?.estado === 'publicado';

  return (
    <div
      className={cn(
        'border bg-background p-3 transition-colors hover:border-primary/30',
        cancelada ? 'opacity-60' : '',
      )}
    >
      <div className="flex items-start justify-between gap-3">
        <div className="flex min-w-0 items-center gap-3">
          <div
            className={cn(
              'flex size-9 shrink-0 items-center justify-center',
              cancelada
                ? 'bg-muted text-muted-foreground'
                : 'bg-primary/10 text-primary',
            )}
          >
            <GraduationCap className="size-4" />
          </div>
          <div className="min-w-0">
            <h3 className="truncate text-sm font-semibold leading-tight">
              {slot.disciplina_nome}
            </h3>
            <div className="mt-0.5 flex items-center gap-1.5 text-xs text-muted-foreground">
              <Clock className="size-3 shrink-0" />
              <span>
                {slot.hora_inicio} – {slot.hora_fim}
              </span>
            </div>
          </div>
        </div>

        {cancelada && (
          <Badge variant="destructive" className="shrink-0 text-[10px]">
            <XCircle className="mr-1 size-3" />
            Cancelada
          </Badge>
        )}
        {!cancelada && !temSumario && isPassado && (
          <Badge variant="outline" className="shrink-0 text-[10px]">
            Sem sumário
          </Badge>
        )}
        {!cancelada && !temSumario && !isPassado && temPrevisto && (
          <Badge variant="secondary" className="shrink-0 gap-1 text-[10px]">
            <NotebookPen className="size-3" />
            Previsto
          </Badge>
        )}
        {temSumario && (
          <Badge className="shrink-0 bg-green-600 text-[10px] hover:bg-green-600">
            ✓ Publicado
          </Badge>
        )}
      </div>

      {aula?.titulo && (
        <p className="mt-2 text-sm font-medium">{aula.titulo}</p>
      )}

      <div className="mt-2 space-y-2">
        {temPrevisto && (
          <div className="border bg-muted/30 p-2">
            <p className="flex items-center gap-1.5 text-[10px] font-medium uppercase tracking-wide text-muted-foreground">
              <NotebookPen className="size-3" />
              Conteúdo previsto
            </p>
            <p className="mt-1 text-sm text-muted-foreground">
              {aula.conteudo_previsto}
            </p>
          </div>
        )}

        {temSumario && (
          <div className="border border-primary/20 bg-primary/5 p-2">
            <p className="flex items-center gap-1.5 text-[10px] font-medium uppercase tracking-wide text-primary">
              <FileText className="size-3" />
              Sumário
            </p>
            <p className="mt-1 whitespace-pre-wrap text-sm">{aula.sumario}</p>
          </div>
        )}

        {temSumario && aula?.tpc && (
          <div className="border border-amber-200 bg-amber-50 p-2 dark:border-amber-800 dark:bg-amber-950/20">
            <p className="flex items-center gap-1.5 text-[10px] font-medium uppercase tracking-wide text-amber-700 dark:text-amber-400">
              <BookOpen className="size-3" />
              Trabalho para casa
            </p>
            <p className="mt-1 whitespace-pre-wrap text-sm text-amber-800 dark:text-amber-300">
              {aula.tpc}
            </p>
          </div>
        )}

        {!cancelada && !temSumario && !temPrevisto && !isPassado && (
          <p className="text-xs italic text-muted-foreground">
            O professor ainda não publicou informação para esta aula.
          </p>
        )}

        {!cancelada && !temSumario && isPassado && (
          <p className="text-xs italic text-muted-foreground">
            Sem sumário registado.
          </p>
        )}

        {cancelada && (
          <p className="text-xs italic text-muted-foreground">
            Esta aula foi cancelada.
          </p>
        )}
      </div>

      {(temSumario || temPrevisto) && aula && (
        <div className="mt-2 flex justify-end border-t pt-2">
          <Button
            variant="ghost"
            size="sm"
            asChild
            className="hover:cursor-pointer hover:text-primary"
          >
            <Link href={`/dashboard/aulas/${aula.id}`}>
              Ver detalhes
              <Eye className="ml-1.5 size-4" />
            </Link>
          </Button>
        </div>
      )}
    </div>
  );
}