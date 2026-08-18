import { useDialog } from '@/hooks/use-dialog';
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent } from '@/components/ui/tabs';
import { EmptyState } from '@/components/empty-state';
import TablePagination from '@/components/table-pagination';
import { useRef, useState } from 'react';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectLabel,
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
import { toast } from 'sonner';
import { BookOpenIcon, UsersIcon, Haze } from 'lucide-react';
import { Link, router } from '@inertiajs/react';
import { create as createDisciplina } from '@/actions/App/Http/Controllers/ClasseTurnoDisciplinaController';
import { destroy } from '@/actions/App/Http/Controllers/ClasseTurnoDisciplinaController';
import {
  show as showTurma,
  create as createTurma,
  edit as editTurma,
} from '@/actions/App/Http/Controllers/ClasseTurnoTurmaController';
import { create } from '@/actions/App/Http/Controllers/CursoClasseTurnoController';
import { cn } from '@/lib/utils';
import { Header } from './components/classe-header';

export default function Show({
  instituicao,
  cursoTutelado,
  cursoClasse,
  anosLectivos,
  anoLectivoActual,
  can,
}) {
  const turnos = cursoClasse.turnos || [];
  const turmas = cursoClasse.turmas;
  const disciplinas = cursoClasse.disciplinas;
  const [anoLectivoSelecionado, setAnoLectivoSelecionado] =
    useState(anoLectivoActual);

  const selectedTurnoId = cursoClasse.turnoId;

  const { deleteConfirm } = useDialog();

  const lastTurnoRef = useRef(null);

  // Parâmetros base reutilizáveis
  const params = {
    instituicao,
    cursoTutelado,
    cursoClasse,
    cursoClasseTurno: selectedTurnoId,
  };

  const handleTurnoChange = (turnoId) => {
    if (lastTurnoRef.current === turnoId) {
      return;
    }

    lastTurnoRef.current = turnoId;

    router.get(
      window.location.pathname,
      {
        turno: turnoId,
        ano_lectivo_id: anoLectivoSelecionado,
        page_turmas: 1,
        page_disciplinas: 1,
      },
      {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
          lastTurnoRef.current = null;
        },
      },
    );
  };

  const handlePageChange = (param) => (page) => {
    router.get(
      '',
      {
        turno: selectedTurnoId,
        ano_lectivo_id: anoLectivoSelecionado,
        [param]: page,
      },
      { preserveState: true, preserveScroll: true },
    );
  };

  const handleDeleteDisciplina = (disciplinaId) => {
    deleteConfirm({
      title: 'Tens a certeza?',
      description: 'Esta disciplina será removida deste turno.',
      confirmLabel: 'Remover',
      confirmFn: () =>
        router.delete(
          destroy[
            '/dashboard/instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/disciplinas/{classeTurnoDisciplina}'
          ]({
            ...params,
            classeTurnoDisciplina: disciplinaId,
          }).url,
          {
            preserveScroll: true,
            data: {
              ano_lectivo_id: anoLectivoSelecionado,
            },

            onSuccess: () => {
              toast.success('Disciplina removida com sucesso');
            },
            onError: (errors) => {
              toast.error(errors.message);
            },
          },
        ),
    });
  };

  const handleAnoLectivoChange = (value) => {
    setAnoLectivoSelecionado(value);

    router.get(
      window.location.pathname,
      {
        turno: selectedTurnoId,
        ano_lectivo_id: value,
        page_turmas: 1,
        page_disciplinas: 1,
      },
      {
        preserveState: true,
        preserveScroll: true,
      },
    );
  };

  const anoLectivoAtualNome = anosLectivos.find(
    (ano) => ano.id === anoLectivoActual,
  )?.nome;

  return (
    <div className="mx-auto w-full max-w-6xl space-y-4 p-6">
      {/* Header com Breadcrumb */}
      <Header
        can={can}
        turnos={turnos}
        params={params}
        anoLectivoAtualNome={anoLectivoAtualNome}
      />

      {turnos.length > 0 ? (
        <>
          {/* Grid de Turnos — mesma estilização do Card */}
          <div className="overflow-hidden bg-card ring-1 ring-foreground/10">
            <div
              className="grid"
              style={{
                gridTemplateColumns: `repeat(${turnos.length}, 1fr)`,
              }}
            >
              {turnos.map((turno, index) => {
                const isActive = selectedTurnoId === turno.id;
                const isLastInRow = index === turnos.length - 1;

                return (
                  <button
                    key={turno.id}
                    type="button"
                    onClick={() => handleTurnoChange(turno.id)}
                    className={cn(
                      'cursor-pointer bg-card px-4 py-4 text-left text-card-foreground transition-colors hover:bg-muted/50',
                      isActive ? 'text-secondary' : '',
                      !isLastInRow && 'border-r border-foreground/10',
                    )}
                  >
                    <h3 className="mb-1 text-sm font-medium">{turno.nome}</h3>
                    <p className="text-xs text-muted-foreground">
                      {isActive ? 'A ver' : 'Clique aqui para ver'}
                    </p>
                  </button>
                );
              })}
            </div>
          </div>

          {/* Select de Ano Lectivo */}
          <div className="flex justify-end">
            <Select
              value={anoLectivoSelecionado}
              onValueChange={handleAnoLectivoChange}
            >
              <SelectTrigger className="md:w-40 w-full">
                <SelectValue placeholder="Selecione o ano lectivo" />
              </SelectTrigger>
              <SelectContent>
                <SelectGroup>
                  <SelectLabel>Anos Lectivos</SelectLabel>
                  {anosLectivos.map((ano) => (
                    <SelectItem key={ano?.id} value={ano?.id}>
                      {ano?.nome}
                    </SelectItem>
                  ))}
                </SelectGroup>
              </SelectContent>
            </Select>
          </div>

          {/* Conteúdo das Tabs */}
          <Tabs value={selectedTurnoId}>
            <TabsContent value={selectedTurnoId} className="mt-2 space-y-6">
              <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {/* DISCIPLINAS */}
                <Card className="grid grid-rows-[auto_1fr_auto] gap-0 overflow-hidden">
                  <CardHeader className="border-b">
                    <CardTitle className="flex! gap-2">
                      Disciplinas ({disciplinas?.total ?? 0})
                    </CardTitle>

                    <CardDescription>
                      Disciplinas do turno da{' '}
                      <span>
                        {
                          turnos.find((turno) => turno.id === selectedTurnoId)
                            ?.nome
                        }
                      </span>
                    </CardDescription>

                    {can.disciplina.create && (
                      <CardAction>
                        <Button asChild size="sm">
                          <Link
                            data={{ redirect_to: window.location.href }}
                            href={`${
                              createDisciplina({
                                ...params,
                              }).url
                            }${anoLectivoSelecionado ? `?ano_lectivo_id=${encodeURIComponent(anoLectivoSelecionado)}` : ''}`}
                          >
                            Adicionar Disciplina
                          </Link>
                        </Button>
                      </CardAction>
                    )}
                  </CardHeader>

                  {!disciplinas?.data?.length ? (
                    <CardContent className="flex items-center justify-center">
                      <EmptyState
                        variant="table"
                        icon={BookOpenIcon}
                        title="Nenhuma disciplina"
                        description="Este turno ainda não tem disciplinas associadas"
                        action={
                          can.disciplina.create
                            ? {
                                label: 'Adicionar Disciplina',
                                href: `${
                                  createDisciplina({
                                    ...params,
                                  }).url
                                }${anoLectivoSelecionado ? `?ano_lectivo_id=${encodeURIComponent(anoLectivoSelecionado)}` : ''}`,
                                variant: 'outline',
                              }
                            : undefined
                        }
                      />
                    </CardContent>
                  ) : (
                    <>
                      <CardContent className="overflow-y-auto p-0!">
                        <Table>
                          <TableHeader>
                            <TableRow className="bg-muted/72">
                              <TableHead className="px-4">Sigla</TableHead>
                              <TableHead className="text-center">
                                Nome
                              </TableHead>
                              <TableHead className="px-4 text-right">
                                Acções
                              </TableHead>
                            </TableRow>
                          </TableHeader>

                          <TableBody>
                            {disciplinas.data.map((disc) => (
                              <TableRow key={disc.id}>
                                <TableCell className="px-4 font-medium">
                                  {disc.disciplina.sigla}
                                </TableCell>

                                <TableCell className="text-center">
                                  {disc.disciplina.nome}
                                </TableCell>

                                <TableCell className="px-4 text-right">
                                  <Button
                                    size="xs"
                                    variant="destructive"
                                    className="text-[10px]"
                                    onClick={(e) => {
                                      e.stopPropagation();
                                      handleDeleteDisciplina(disc.id);
                                    }}
                                  >
                                    Remover
                                  </Button>
                                </TableCell>
                              </TableRow>
                            ))}
                          </TableBody>
                        </Table>
                      </CardContent>

                      <TablePagination
                        pagination={{
                          current_page: disciplinas.current_page,
                          last_page: disciplinas.last_page,
                        }}
                        onPageChange={handlePageChange('page_disciplinas')}
                      />
                    </>
                  )}
                </Card>

                {/* TURMAS */}
                <Card className="grid grid-rows-[auto_1fr_auto] gap-0 overflow-hidden">
                  <CardHeader className="border-b">
                    <CardTitle className="flex! gap-2">
                      Turmas ({turmas?.total ?? 0})
                    </CardTitle>

                    <CardDescription>
                      Turmas do turno da{' '}
                      <span>
                        {
                          turnos.find((turno) => turno.id === selectedTurnoId)
                            ?.nome
                        }
                      </span>
                    </CardDescription>

                    {can.turma.create && (
                      <CardAction>
                        <Button asChild size="sm">
                          <Link
                            href={
                              createTurma({
                                ...params,
                              }).url
                            }
                          >
                            Adicionar Turma
                          </Link>
                        </Button>
                      </CardAction>
                    )}
                  </CardHeader>

                  {!turmas?.data?.length ? (
                    <CardContent className="flex items-center justify-center">
                      <EmptyState
                        variant="table"
                        icon={UsersIcon}
                        title="Nenhuma turma"
                        description="Este turno ainda não tem turmas adicionadas"
                        action={
                          can.turma.create
                            ? {
                                label: 'Adicionar Turma',
                                href: createTurma({
                                  ...params,
                                }).url,
                                variant: 'outline',
                              }
                            : undefined
                        }
                      />
                    </CardContent>
                  ) : (
                    <>
                      <CardContent className="overflow-y-auto p-0!">
                        <Table>
                          <TableHeader>
                            <TableRow className="bg-muted/72">
                              <TableHead className="px-4">Nome</TableHead>
                              <TableHead className="text-center">
                                Alunos
                              </TableHead>
                              <TableHead className="px-4 text-right">
                                Acções
                              </TableHead>
                            </TableRow>
                          </TableHeader>

                          <TableBody>
                            {turmas.data.map((turma) => (
                              <TableRow
                                key={turma.id}
                                aria-disabled={!turma.can?.view}
                                className="hover:cursor-pointer aria-disabled:cursor-not-allowed aria-disabled:opacity-70 aria-disabled:hover:bg-transparent"
                                onClick={() => {
                                  if (turma.can?.view) {
                                    router.visit(
                                      showTurma({
                                        ...params,
                                        turma: turma.id,
                                      }).url,
                                    );
                                  }
                                }}
                              >
                                <TableCell className="px-4 font-medium">
                                  {turma.nome}
                                </TableCell>

                                <TableCell className="text-center">
                                  {turma.alunos_activos_count}
                                </TableCell>

                                <TableCell className="px-4 text-right">
                                  {turma?.can?.edit && (
                                    <Button
                                      variant="outline"
                                      size="xs"
                                      className="text-[10px]"
                                      onClick={(e) => {
                                        e.stopPropagation();
                                        router.visit(
                                          editTurma({
                                            ...params,
                                            turma: turma.id,
                                          }).url + '?origem=classe',
                                        );
                                      }}
                                    >
                                      Editar
                                    </Button>
                                  )}
                                </TableCell>
                              </TableRow>
                            ))}
                          </TableBody>
                        </Table>
                      </CardContent>

                      <TablePagination
                        pagination={{
                          current_page: turmas.current_page,
                          last_page: turmas.last_page,
                        }}
                        onPageChange={handlePageChange('page_turmas')}
                      />
                    </>
                  )}
                </Card>
              </div>
            </TabsContent>
          </Tabs>
        </>
      ) : (
        <Card>
          <CardContent className="flex items-center justify-center py-20">
            <EmptyState
              icon={Haze}
              variant="table"
              title="Nenhum turno"
              description="Esta classe ainda não tem turnos definidos"
              action={
                can.turno.create
                  ? {
                      label: 'Adicionar Turno',
                      onClick: () =>
                        router.visit(
                          create({
                            instituicao: params.instituicao,
                            cursoTutelado: params.cursoTutelado,
                            cursoClasse: params.cursoClasse,
                          }).url,
                        ),
                      variant: 'outline',
                    }
                  : undefined
              }
            />
          </CardContent>
        </Card>
      )}
    </div>
  );
}
