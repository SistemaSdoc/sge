import { useMemo, useState } from 'react';
import { CalendarClock, ChevronDownIcon } from 'lucide-react';
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

const hoje = () => {
  const d = new Date().getDay(); // 0 = domingo
  return d === 0 ? 7 : d;
};

// Uma disciplina por bloco, com as turmas agrupadas por baixo
const agruparPorDisciplina = (lista) => {
  const mapa = new Map();

  lista.forEach((a) => {
    const grupo = mapa.get(a.disciplina.nome) ?? {
      disciplina: a.disciplina,
      turmas: [],
    };
    grupo.turmas.push({ id: a.turma.id, nome: a.turma.nome, sala: a.sala });
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
  aulas,
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
    <Card className="mb-0 gap-0">
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
          <Table className="min-w-[760px] table-fixed">
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="w-36 px-4 text-center">Hora</TableHead>
                {dias.map((d) => (
                  <TableHead key={d.id} className="px-4 text-center">
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
                        className="px-4 text-center align-middle"
                      >
                        {grupos.length > 0 && (
                          <div className="flex flex-col gap-2">
                            {grupos.map((g) => (
                              <div
                                key={g.disciplina.nome}
                                title={g.disciplina.nome}
                                className="flex flex-col gap-1"
                              >
                                <p>{g.disciplina.sigla}</p>

                                {mostrarTurma ? (
                                  <p className="text-xs text-muted-foreground">
                                    {g.turmas.map((t) => t.nome).join(', ')}
                                  </p>
                                ) : (
                                  g.turmas[0]?.sala && (
                                    <p className="text-xs text-muted-foreground">
                                      Sala {g.turmas[0].sala}
                                    </p>
                                  )
                                )}
                              </div>
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
