import { useState, useEffect } from 'react';
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
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { EmptyState } from '@/components/empty-state';
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  ArrowLeftIcon,
  MoreHorizontalIcon,
  BookOpenIcon,
  UsersIcon,
  CheckCircle2Icon,
  CircleX,
} from 'lucide-react';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Link, router } from '@inertiajs/react';
import { show as showCurso } from '@/actions/App/Http/Controllers/CursoTuteladoController';
import { create as createDisciplina } from '@/actions/App/Http/Controllers/ClasseTurnoDisciplinaController';
import {
  show as showTurma,
  create as createTurma,
} from '@/actions/App/Http/Controllers/ClasseTurnoTurmaController';

export default function Show({ instituicao, cursoTutelado, cursoClasse }) {
  const instituicaoId = instituicao.id;
  const cursoId = cursoTutelado.id;
  const classeId = cursoClasse.id;
  const classe = cursoClasse.classe;
  const curso = cursoTutelado.curso;
  const turnos = cursoClasse.turnos || [];
  const [selectedTurnoId, setSelectedTurnoId] = useState(null);
  console.log(turnos);
  // Auto-select first turno
  useEffect(() => {
    if (turnos.length > 0 && !selectedTurnoId) {
      setSelectedTurnoId(turnos[0].id);
    }
  }, [turnos, selectedTurnoId]);

  const selectedTurno = turnos?.find((t) => t.id === selectedTurnoId);
  const totalDisciplinas = selectedTurno?.disciplinas?.length || 0;

  return (
    <div className="mx-auto w-full max-w-6xl space-y-4 p-6">
      {/* Header with gradient background */}
      <Card className="gap-0">
        <CardHeader>
          <CardTitle className="text-xl">{classe?.nome}</CardTitle>

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
                        cursoTutelado: cursoTutelado.id,
                      }).url,
                    )
                  }
                >
                  <ArrowLeftIcon strokeWidth={1.5} size={5} />
                  Voltar ao curso
                </DropdownMenuItem>
                <DropdownMenuSeparator />
              </DropdownMenuContent>
            </DropdownMenu>
          </CardAction>
        </CardHeader>
      </Card>

      {/* Turnos Navigation */}
      {turnos?.length > 0 && (
        <div>
          <Tabs value={selectedTurnoId} onValueChange={setSelectedTurnoId}>
            <TabsList className="">
              {turnos.map((turno) => (
                <TabsTrigger key={turno.id} value={turno.id}>
                  <span className="font-medium">{turno.nome}</span>
                </TabsTrigger>
              ))}
            </TabsList>

            <TabsContent value={selectedTurnoId} className="mt-2 space-y-6">
              <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <Card className="flex flex-col gap-0">
                  <CardHeader className="border-b">
                    <CardTitle className="flex! gap-2">
                      <BookOpenIcon className="size-5 text-primary" />
                      Disciplinas ({totalDisciplinas})
                    </CardTitle>

                    <CardDescription>
                      Disciplinas do turno da {selectedTurno?.nome}
                    </CardDescription>

                    <CardAction>
                      <Button asChild size="sm">
                        <Link
                          href={
                            selectedTurnoId
                              ? createDisciplina({
                                  instituicao: instituicaoId,
                                  cursoTutelado: cursoId,
                                  cursoClasse: classeId,
                                  cursoClasseTurno: selectedTurnoId,
                                }).url
                              : '#'
                          }
                        >
                          Adicionar
                        </Link>
                      </Button>
                    </CardAction>
                  </CardHeader>

                  {totalDisciplinas === 0 ? (
                    <CardContent className="flex flex-1 items-center justify-center">
                      <EmptyState
                        variant="table"
                        icon={BookOpenIcon}
                        title="Nenhuma disciplina"
                        description="Este turno ainda não tem disciplinas associadas"
                        action={{
                          label: 'Associar disciplinas',
                          href: selectedTurnoId
                            ? createDisciplina({
                                instituicao: instituicaoId,
                                cursoTutelado: cursoId,
                                cursoClasse: classeId,
                                cursoClasseTurno: selectedTurnoId,
                              }).url
                            : '#',
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
                                Acções
                              </TableHead>
                            </TableRow>
                          </TableHeader>

                          <TableBody>
                            {selectedTurno?.disciplinas?.map((disc) => (
                              <TableRow
                                key={disc.id}
                                className="hover:cursor-pointer"
                              >
                                <TableCell className="px-4 font-medium">
                                  {disc.sigla}
                                </TableCell>
                                <TableCell>{disc.nome}</TableCell>
                                <TableCell className="px-4 text-right">
                                  <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                      <Button
                                        variant="ghost"
                                        size="icon"
                                        className="size-8"
                                      >
                                        <MoreHorizontalIcon />
                                        <span className="sr-only">
                                          Open menu
                                        </span>
                                      </Button>
                                    </DropdownMenuTrigger>

                                    {/* <DropdownMenuContent align="end">
                                      <DropdownMenuItem onClick={(e) => {
                                        e.stopPropagation()
                                        router.push(`/dashboard/instituicoes/${instituicao.id}/edit`)
                                      }}>
                                        Editar
                                      </DropdownMenuItem>

                                      <DropdownMenuSeparator />

                                      <DropdownMenuItem variant="destructive" onClick={(e) => {
                                        e.stopPropagation()
                                        deleteFn(instituicao.id)
                                      }}>
                                        Remover
                                      </DropdownMenuItem>
                                    </DropdownMenuContent> */}
                                  </DropdownMenu>
                                </TableCell>
                              </TableRow>
                            ))}
                          </TableBody>
                        </Table>
                      </CardContent>

                      <CardFooter className="justify-between border-t">
                        <span className="text-muted-foreground">
                          Página 1 de 4
                        </span>

                        <Pagination>
                          <PaginationContent>
                            <PaginationItem>
                              <PaginationPrevious href="#" />
                            </PaginationItem>
                            <PaginationItem>
                              <PaginationNext href="#" />
                            </PaginationItem>
                          </PaginationContent>
                        </Pagination>
                      </CardFooter>
                    </>
                  )}
                </Card>

                {/* Turmas Section */}
                <Card className="flex flex-col gap-0">
                  <CardHeader className="border-b">
                    <CardTitle className="flex! gap-2">
                      <UsersIcon className="size-5 text-primary" />
                      Turmas ({selectedTurno?.turmas?.length || 0})
                    </CardTitle>

                    <CardDescription>
                      Turmas do turno da {selectedTurno?.nome}
                    </CardDescription>

                    <CardAction>
                      <Button asChild size="sm">
                        <Link
                          href={
                            selectedTurnoId
                              ? createTurma({
                                  instituicao: instituicaoId,
                                  cursoTutelado: cursoId,
                                  cursoClasse: classeId,
                                  cursoClasseTurno: selectedTurnoId,
                                }).url
                              : '#'
                          }
                        >
                          Adicionar
                        </Link>
                      </Button>
                    </CardAction>
                  </CardHeader>

                  {!selectedTurno?.turmas ||
                  selectedTurno.turmas.length === 0 ? (
                    <CardContent className="flex flex-1 items-center justify-center">
                      <EmptyState
                        variant="table"
                        icon={UsersIcon}
                        title="Nenhuma turma"
                        description="Este turno ainda não tem turmas criadas"
                        action={{
                          label: 'Adicionar Turma',
                          href: selectedTurnoId
                            ? createTurma({
                                instituicao: instituicaoId,
                                cursoTutelado: cursoId,
                                cursoClasse: classeId,
                                cursoClasseTurno: selectedTurnoId,
                              }).url
                            : '#',
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
                                Acções
                              </TableHead>
                            </TableRow>
                          </TableHeader>

                          <TableBody>
                            {selectedTurno.turmas.map((turma) => (
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
                                <TableCell>{turma.alunos_count}</TableCell>
                                <TableCell className="px-4 text-right">
                                  <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                      <Button
                                        variant="ghost"
                                        size="icon"
                                        className="size-8"
                                      >
                                        <MoreHorizontalIcon />
                                        <span className="sr-only">
                                          Open menu
                                        </span>
                                      </Button>
                                    </DropdownMenuTrigger>

                                    <DropdownMenuContent align="end">
                                      <DropdownMenuItem
                                        onClick={(e) => {
                                          e.stopPropagation();
                                          router.visit(
                                            `/instituicoes/${instituicaoId}/cursos-tutelados/${cursoId}/classes/${classeId}/turnos/${selectedTurnoId}/turmas/${turma.id}/edit`,
                                          );
                                        }}
                                      >
                                        Editar
                                      </DropdownMenuItem>

                                      <DropdownMenuSeparator />

                                      <DropdownMenuItem
                                        variant="destructive"
                                        onClick={(e) => {
                                          e.stopPropagation();
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

                      <CardFooter className="justify-between border-t">
                        <span className="text-muted-foreground">
                          {selectedTurno?.turmas.length} turma
                          {selectedTurno?.turmas.length !== 1 ? 's' : ''}
                        </span>

                        <Pagination>
                          <PaginationContent>
                            <PaginationItem>
                              <PaginationPrevious href="#" />
                            </PaginationItem>
                            <PaginationItem>
                              <PaginationNext href="#" />
                            </PaginationItem>
                          </PaginationContent>
                        </Pagination>
                      </CardFooter>
                    </>
                  )}
                </Card>
              </div>
            </TabsContent>
          </Tabs>
        </div>
      )}

      {/* Empty state if no turnos */}
      {turnos?.length === 0 && (
        <Card>
          <CardContent className="flex items-center justify-center py-20">
            <EmptyState
              icon={BookOpenIcon}
              title="Nenhum turno"
              description="Esta classe ainda não tem turnos associados"
            />
          </CardContent>
        </Card>
      )}
    </div>
  );
}
