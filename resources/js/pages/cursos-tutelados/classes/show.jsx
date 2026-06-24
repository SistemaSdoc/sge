import { useDialog } from '@/hooks/use-dialog';
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { EmptyState } from '@/components/empty-state';
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
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { toast } from 'sonner';
import {
  ArrowLeftIcon,
  MoreHorizontalIcon,
  BookOpenIcon,
  UsersIcon,
  ChevronLeft,
  ChevronRight,
} from 'lucide-react';
import { Link, router } from '@inertiajs/react';
import { show as showCurso } from '@/actions/App/Http/Controllers/CursoTuteladoController';
import { create as createDisciplina } from '@/actions/App/Http/Controllers/ClasseTurnoDisciplinaController';
import { destroy } from '@/actions/App/Http/Controllers/ClasseTurnoDisciplinaController';
import {
  show as showTurma,
  create as createTurma,
} from '@/actions/App/Http/Controllers/ClasseTurnoTurmaController';
import { create } from '@/actions/App/Http/Controllers/CursoClasseTurnoController';
import TablePagination from '@/components/table-pagination';

function Paginacao({ paginacao, onAnterior, onSeguinte }) {
  if (!paginacao || paginacao.last_page <= 1) return null;
  return (
    <div className="flex items-center gap-1">
      <Button
        variant="ghost"
        size="sm"
        disabled={paginacao.current_page === 1}
        onClick={onAnterior}
      >
        <ChevronLeft className="size-4" /> Anterior
      </Button>
      <Button
        variant="ghost"
        size="sm"
        disabled={paginacao.current_page === paginacao.last_page}
        onClick={onSeguinte}
      >
        Proxima <ChevronRight className="size-4" />
      </Button>
    </div>
  );
}

export default function Show({ instituicao, cursoTutelado, cursoClasse }) {
  const instituicaoId = instituicao.id;
  const cursoId = cursoTutelado.id;
  const classeId = cursoClasse.id;
  const turnos = cursoClasse.turnos || [];
  const turmas = cursoClasse.turmas;
  const disciplinas = cursoClasse.disciplinas;

  const selectedTurnoId = cursoClasse.turnoId;

  const { deleteConfirm } = useDialog();

  const handleTurnoChange = (turnoId) => {
    router.get(
      '',
      { turno: turnoId, page_turmas: 1, page_disciplinas: 1 },
      { preserveState: true, preserveScroll: true },
    );
  };

  const handlePageChange = (param) => (page) => {
    router.get(
      '',
      { turno: selectedTurnoId, [param]: page },
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
            instituicao: instituicaoId,
            cursoTutelado: cursoId,
            cursoClasse: classeId,
            cursoClasseTurno: selectedTurnoId,
            classeTurnoDisciplina: disciplinaId,
          }).url,
          {
            preserveScroll: true,

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

  return (
    <div className="mx-auto w-full max-w-6xl space-y-4 p-6">
      <Card className="gap-0">
        <CardHeader>
          <CardTitle className="text-xl">{cursoClasse.classe?.nome}</CardTitle>
          <CardDescription>
            Gerir disciplinas e turmas por turno
          </CardDescription>
          <CardAction>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon">
                  <MoreHorizontalIcon className="size-5" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem
                  onClick={() =>
                    router.visit(
                      showCurso({
                        instituicao: instituicaoId,
                        cursoTutelado: cursoId,
                      }).url,
                    )
                  }
                >
                  <ArrowLeftIcon strokeWidth={1.5} /> Voltar ao curso
                </DropdownMenuItem>
                <DropdownMenuSeparator />
              </DropdownMenuContent>
            </DropdownMenu>
          </CardAction>
        </CardHeader>
      </Card>

      {turnos.length > 0 ? (
        <Tabs value={selectedTurnoId} onValueChange={handleTurnoChange}>
          <TabsList>
            {turnos.map((turno) => (
              <TabsTrigger key={turno.id} value={turno.id}>
                {turno.nome}
              </TabsTrigger>
            ))}

            {turnos.length < 3 && (
              <Button
                variant="ghost"
                size="icon"
                className="size-7"
                onClick={() =>
                  router.visit(
                    create({
                      instituicao: instituicaoId,
                      cursoTutelado: cursoId,
                      cursoClasse: cursoClasse.id,
                    }).url,
                  )
                }
              >
                +
              </Button>
            )}
          </TabsList>

          <TabsContent value={selectedTurnoId} className="mt-2 space-y-6">
            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
              <Card className="flex flex-col gap-0">
                <CardHeader className="border-b">
                  <CardTitle className="flex! gap-2">
                    <BookOpenIcon className="size-5 text-primary" />
                    Disciplinas ({disciplinas?.total ?? 0})
                  </CardTitle>
                  <CardDescription>
                    Disciplinas do turno selecionado
                  </CardDescription>
                  <CardAction>
                    <Button asChild size="sm">
                      <Link
                        data={{ redirect_to: window.location.href }}
                        href={
                          createDisciplina({
                            instituicao: instituicaoId,
                            cursoTutelado: cursoId,
                            cursoClasse: classeId,
                            cursoClasseTurno: selectedTurnoId,
                          }).url
                        }
                      >
                        Adicionar
                      </Link>
                    </Button>
                  </CardAction>
                </CardHeader>
                {!disciplinas?.data?.length ? (
                  <CardContent className="flex flex-1 items-center justify-center">
                    <EmptyState
                      variant="table"
                      icon={BookOpenIcon}
                      title="Nenhuma disciplina"
                      description="Este turno ainda não tem disciplinas associadas"
                      action={{
                        label: 'Associar disciplinas',
                        href: createDisciplina({
                          instituicao: instituicaoId,
                          cursoTutelado: cursoId,
                          cursoClasse: classeId,
                          cursoClasseTurno: selectedTurnoId,
                        }).url,
                        variant: 'outline',
                      }}
                    />
                  </CardContent>
                ) : (
                  <>
                    <CardContent className="p-0!">
                      <Table>
                        <TableHeader>
                          <TableRow className="bg-muted/72">
                            <TableHead className="px-4">Sigla</TableHead>
                            <TableHead>Nome</TableHead>
                            <TableHead className="px-4 text-right">
                              Accoes
                            </TableHead>
                          </TableRow>
                        </TableHeader>
                        <TableBody>
                          {disciplinas.data.map((disc) => (
                            <TableRow key={disc.id}>
                              <TableCell className="px-4 font-medium">
                                {disc.disciplina.sigla}
                              </TableCell>
                              <TableCell>{disc.disciplina.nome}</TableCell>
                              <TableCell className="px-4 text-right">
                                <DropdownMenu>
                                  <DropdownMenuTrigger asChild>
                                    <Button
                                      variant="ghost"
                                      size="icon"
                                      className="size-8"
                                      onClick={(e) => e.stopPropagation()}
                                    >
                                      <MoreHorizontalIcon />
                                    </Button>
                                  </DropdownMenuTrigger>
                                  <DropdownMenuContent align="end">
                                    {/*<DropdownMenuItem
                                                                            onClick={(e) => {
                                                                                e.stopPropagation();

                                                                                router.visit(
                                                                                    editDisciplina({
                                                                                        instituicao: instituicaoId,
                                                                                        cursoTutelado: cursoId,
                                                                                        cursoClasse: classeId,
                                                                                        cursoClasseTurno: selectedTurnoId,
                                                                                        classeTurnoDisciplina: disc.id,
                                                                                    }).url
                                                                                );
                                                                            }}
                                                                        >
                                                                            Editar
                                                                        </DropdownMenuItem>*/}
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                      variant="destructive"
                                      onClick={(e) => {
                                        e.stopPropagation();
                                        handleDeleteDisciplina(disc.id);
                                      }}
                                    >
                                      Remover
                                    </DropdownMenuItem>
                                  </DropdownMenuContent>
                                </DropdownMenu>
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

              <Card className="flex flex-col gap-0">
                <CardHeader className="border-b">
                  <CardTitle className="flex! gap-2">
                    <UsersIcon className="size-5 text-primary" />
                    Turmas ({turmas?.total ?? 0})
                  </CardTitle>
                  <CardDescription>Turmas do turno selecionado</CardDescription>
                  <CardAction>
                    <Button asChild size="sm">
                      <Link
                        href={
                          createTurma({
                            instituicao: instituicaoId,
                            cursoTutelado: cursoId,
                            cursoClasse: classeId,
                            cursoClasseTurno: selectedTurnoId,
                          }).url
                        }
                      >
                        Adicionar
                      </Link>
                    </Button>
                  </CardAction>
                </CardHeader>
                {!turmas?.data?.length ? (
                  <CardContent className="flex flex-1 items-center justify-center">
                    <EmptyState
                      variant="table"
                      icon={UsersIcon}
                      title="Nenhuma turma"
                      description="Este turno ainda não tem turmas criadas"
                      action={{
                        label: 'Adicionar Turma',
                        href: createTurma({
                          instituicao: instituicaoId,
                          cursoTutelado: cursoId,
                          cursoClasse: classeId,
                          cursoClasseTurno: selectedTurnoId,
                        }).url,
                        variant: 'outline',
                      }}
                    />
                  </CardContent>
                ) : (
                  <>
                    <CardContent className="p-0!">
                      <Table>
                        <TableHeader>
                          <TableRow className="bg-muted/72">
                            <TableHead className="px-4">Nome</TableHead>
                            <TableHead>Alunos</TableHead>
                            <TableHead className="px-4 text-right">
                              Accoes
                            </TableHead>
                          </TableRow>
                        </TableHeader>
                        <TableBody>
                          {turmas.data.map((turma) => (
                            <TableRow
                              key={turma.id}
                              className="hover:cursor-pointer"
                              onClick={() =>
                                router.visit(
                                  showTurma({
                                    instituicao: instituicaoId,
                                    cursoTutelado: cursoId,
                                    cursoClasse: classeId,
                                    cursoClasseTurno: selectedTurnoId,
                                    turma: turma.id,
                                  }).url,
                                )
                              }
                            >
                              <TableCell className="px-4 font-medium">
                                {turma.nome}
                              </TableCell>
                              <TableCell>
                                {turma.alunos_activos_count}
                              </TableCell>
                              <TableCell className="px-4 text-right">
                                <DropdownMenu>
                                  <DropdownMenuTrigger asChild>
                                    <Button
                                      variant="ghost"
                                      size="icon"
                                      className="size-8"
                                      onClick={(e) => e.stopPropagation()}
                                    >
                                      <MoreHorizontalIcon />
                                    </Button>
                                  </DropdownMenuTrigger>
                                  <DropdownMenuContent align="end">
                                    <DropdownMenuItem
                                      onClick={(e) => {
                                        e.stopPropagation();
                                        router.visit(
                                          `/dashboard/instituicoes/${instituicaoId}/cursos-tutelados/${cursoId}/classes/${classeId}/turnos/${selectedTurnoId}/turmas/${turma.id}/edit?origem=classe`,
                                        );
                                      }}
                                    >
                                      Editar
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    {/*<DropdownMenuItem variant="destructive" onClick={(e) => e.stopPropagation()}>
                                                                            Remover
                                                                        </DropdownMenuItem>*/}
                                  </DropdownMenuContent>
                                </DropdownMenu>
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
      ) : (
        <Card>
          <CardContent className="flex items-center justify-center py-20">
            <EmptyState
              icon={BookOpenIcon}
              title="Nenhum turno"
              description="Esta classe ainda não tem turnos associados"
              action={{
                label: '+ Adicionar Turno',
                onClick: () =>
                  router.visit(
                    create({
                      instituicao: instituicaoId,
                      cursoTutelado: cursoId,
                      cursoClasse: cursoClasse.id,
                    }).url,
                  ),
                variant: 'outline',
              }}
            />
          </CardContent>
        </Card>
      )}
    </div>
  );
}
