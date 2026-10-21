import { useMemo, useState } from 'react';
import { Link } from '@inertiajs/react';
import {
  CalendarClock,
  ChevronDownIcon,
  FileText,
  Pencil,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { EmptyState } from '@/components/empty-state';

// 1 = Segunda ... 7 = Domingo
const DIAS = [
  { id: 1, nome: 'Segunda' },
  { id: 2, nome: 'Terça' },
  { id: 3, nome: 'Quarta' },
  { id: 4, nome: 'Quinta' },
  { id: 5, nome: 'Sexta' },
  { id: 6, nome: 'Sábado' },
  { id: 7, nome: 'Domingo' },
];

// Agrupa por disciplina (mantém o comportamento atual, agora com sumários)
const agruparPorDisciplina = (lista) => {
  const mapa = new Map();

  lista.forEach((a) => {
    const grupo = mapa.get(a.disciplina.nome) ?? {
      disciplina: a.disciplina,
      turmas: [],
    };
    grupo.turmas.push({
      id: a.turma.id,
      nome: a.turma.nome,
      sala: a.sala,
      aula_ultima: a.aula_ultima,
      aula_proxima: a.aula_proxima,
      url_criar: a.url_criar,
    });
    mapa.set(a.disciplina.nome, grupo);
  });

  return [...mapa.values()].map((g) => ({
    ...g,
    turmas: g.turmas.sort((a, b) => a.nome.localeCompare(b.nome)),
  }));
};

function FiltroDropdown({ label, titulo, children }) {
  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button
          variant="outline"
          className="w-full hover:cursor-pointer md:w-auto"
        >
          {label}
          <ChevronDownIcon aria-hidden="true" />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="start" className="md:w-48">
        <DropdownMenuLabel>{titulo}</DropdownMenuLabel>
        <DropdownMenuSeparator />
        {children}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}

export function HorarioTable({
  aulas = [],
  perfil,
  anosLectivos = [],
  anoLectivoActual,
  onAnoLectivoChange,
}) {
  const [turmaSeleccionada, setTurmaSeleccionada] = useState('todas');
  const isProfessor = perfil === 'professor';

  const turmas = useMemo(() => {
    const mapa = new Map(aulas.map((a) => [a.turma.id, a.turma.nome]));
    return [...mapa]
      .map(([id, nome]) => ({ id, nome }))
      .sort((a, b) => a.nome.localeCompare(b.nome));
  }, [aulas]);

  // Se a turma já não existe (ex.: mudou o ano lectivo), volta a "todas"
  const turmaFiltro = turmas.some((t) => t.id === turmaSeleccionada)
    ? turmaSeleccionada
    : 'todas';

  const aulasVisiveis = useMemo(
    () =>
      turmaFiltro === 'todas'
        ? aulas
        : aulas.filter((a) => a.turma.id === turmaFiltro),
    [aulas, turmaFiltro],
  );

  // A sigla da turma só aparece quando há mais de uma em jogo
  const mostrarTurma =
    isProfessor && turmaFiltro === 'todas' && turmas.length > 1;

  const slots = useMemo(() => {
    const mapa = new Map(
      aulasVisiveis.map((a) => [
        `${a.hora_inicio}-${a.hora_fim}`,
        { inicio: a.hora_inicio, fim: a.hora_fim },
      ]),
    );
    return [...mapa.values()].sort((a, b) => a.inicio.localeCompare(b.inicio));
  }, [aulasVisiveis]);

  // Sábado/Domingo só aparecem se houver aulas
  const dias = DIAS.filter(
    (d) => d.id <= 5 || aulasVisiveis.some((a) => a.dia_semana === d.id),
  );

  const aulasDaCelula = (slot, dia) =>
    agruparPorDisciplina(
      aulasVisiveis.filter(
        (a) =>
          a.dia_semana === dia &&
          a.hora_inicio === slot.inicio &&
          a.hora_fim === slot.fim,
      ),
    );

  const anoNome =
    anosLectivos.find((a) => a.id === anoLectivoActual)?.nome ?? 'seleccionado';
  const turmaNome = turmas.find((t) => t.id === turmaFiltro)?.nome;

  return (
    <Card className="gap-0 mb-0">
      <CardHeader className="border-b">
        <CardTitle>Horário</CardTitle>
        <CardDescription>
          {isProfessor ? 'As tuas aulas' : 'O teu horário'} no ano lectivo{' '}
          <span className="font-semibold">{anoNome}</span>
        </CardDescription>
      </CardHeader>

      <CardContent className="p-0!">
        <div className="flex justify-end border-b bg-muted/30 px-4 py-3">
          <div className="flex w-full flex-col gap-2 md:w-auto md:flex-row">
            {isProfessor && turmas.length > 1 && (
              <FiltroDropdown
                titulo="Turmas"
                label={turmaFiltro === 'todas' ? 'Todas as turmas' : turmaNome}
              >
                <DropdownMenuItem
                  onClick={() => setTurmaSeleccionada('todas')}
                  className={`${turmaFiltro === 'todas' ? 'bg-muted' : ''} hover:cursor-pointer`}
                >
                  Todas as turmas
                </DropdownMenuItem>
                {turmas.map((t) => (
                  <DropdownMenuItem
                    key={t.id}
                    onClick={() => setTurmaSeleccionada(t.id)}
                    className={`${turmaFiltro === t.id ? 'bg-muted' : ''} hover:cursor-pointer`}
                  >
                    {t.nome}
                  </DropdownMenuItem>
                ))}
              </FiltroDropdown>
            )}

            <FiltroDropdown titulo="Anos Lectivos" label={anoNome}>
              {anosLectivos.map((ano) => (
                <DropdownMenuItem
                  key={ano.id}
                  onClick={() => onAnoLectivoChange(ano.id)}
                  className={`${anoLectivoActual === ano.id ? 'bg-muted' : ''} hover:cursor-pointer`}
                >
                  {ano.nome}
                </DropdownMenuItem>
              ))}
            </FiltroDropdown>
          </div>
        </div>

        {slots.length === 0 ? (
          <EmptyState
            variant="table"
            icon={CalendarClock}
            title="Nenhum horário disponível"
            description="Ainda não há aulas com horário definido neste ano lectivo."
          />
        ) : (
          <Table className="min-w-[840px] table-fixed">
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="w-36 px-4 text-center">Hora</TableHead>
                {dias.map((d) => (
                  <TableHead key={d.id} className="px-3 text-center">
                    {d.nome}
                  </TableHead>
                ))}
              </TableRow>
            </TableHeader>

            <TableBody>
              {slots.map((slot) => (
                <TableRow key={`${slot.inicio}-${slot.fim}`}>
                  <TableCell className="px-4 text-center align-middle font-medium whitespace-nowrap tabular-nums">
                    {slot.inicio} – {slot.fim}
                  </TableCell>

                  {dias.map((d) => {
                    const grupos = aulasDaCelula(slot, d.id);

                    return (
                      <TableCell
                        key={d.id}
                        className="px-3 align-middle"
                      >
                        {grupos.length > 0 && (
                          <div className="flex flex-col gap-3">
                            {grupos.map((g) => (
                              <CelulaGrupo
                                key={g.disciplina.nome}
                                grupo={g}
                                mostrarTurma={mostrarTurma}
                                isProfessor={isProfessor}
                              />
                            ))}
                          </div>
                        )}
                      </TableCell>
                    );
                  })}
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </CardContent>
    </Card>
  );
}

// ═══════════════════════════════════════════════════════════
// Célula: disciplina + temas
// ═══════════════════════════════════════════════════════════

function CelulaGrupo({ grupo, mostrarTurma, isProfessor }) {
  // Caso multi-turma (ex: professor com 10ªA + 10ªB na mesma disciplina)
  if (mostrarTurma && grupo.turmas.length > 1) {
    return (
      <div className="flex flex-col gap-2">
        <p
          className="text-center text-sm font-semibold"
          title={grupo.disciplina.nome}
        >
          {grupo.disciplina.sigla}
        </p>
        <div className="flex flex-col gap-2">
          {grupo.turmas.map((t) => (
            <TurmaComTema
              key={t.id}
              turma={t}
              isProfessor={isProfessor}
              showTurmaNome
            />
          ))}
        </div>
      </div>
    );
  }

  // Caso simples: 1 turma
  const turma = grupo.turmas[0];

  return (
    <div className="flex flex-col gap-1 text-center">
      <p className="text-sm font-semibold" title={grupo.disciplina.nome}>
        {grupo.disciplina.sigla}
      </p>
      {turma?.sala && (
        <p className="text-[10px] text-muted-foreground">Sala {turma.sala}</p>
      )}
      <TurmaComTema turma={turma} isProfessor={isProfessor} />
    </div>
  );
}

function TurmaComTema({ turma, isProfessor, showTurmaNome = false }) {
  if (!turma) return null;

  const ultima = turma.aula_ultima;
  const proxima = turma.aula_proxima;

  return (
    <div className="flex flex-col gap-1  border border-border/40 bg-muted/20 px-2 py-1.5">
      {showTurmaNome && (
        <p className="text-[10px] font-medium text-muted-foreground">
          {turma.nome}
        </p>
      )}

      {/* Tema da última aula registada OU botão de criar */}
      {ultima?.titulo ? (
        <Link
          href={ultima.url_ver}
          className="group inline-flex items-start gap-1 text-[11px] leading-tight text-primary hover:underline"
          title={ultima.titulo}
        >
          <FileText className="mt-0.5 size-3 shrink-0" />
          <span className="line-clamp-2 text-left">{ultima.titulo}</span>
        </Link>
      ) : isProfessor && turma.url_criar ? (
        <Link
          href={turma.url_criar}
          className="inline-flex items-center gap-1 text-[11px] font-medium text-amber-600 hover:underline dark:text-amber-400"
        >
          <Pencil className="size-3" />
          Escrever sumário
        </Link>
      ) : (
        <p className="text-[11px] italic text-muted-foreground">Sem sumário</p>
      )}

      {/* Próxima aula (aluno + professor) */}
      {proxima?.titulo && (
        <p
          className="line-clamp-1 text-[10px] text-muted-foreground"
          title={`Próximo: ${proxima.titulo}`}
        >
          <span className="font-medium">Próximo:</span> {proxima.titulo}
        </p>
      )}
    </div>
  );
}