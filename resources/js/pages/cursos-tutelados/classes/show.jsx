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
  UserRoundIcon,
  UsersRoundIcon,
  GraduationCapIcon,
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

export default function Show({ 
  instituicao,
  cursoTutelado,
  cursoClasse,
  cursoClasseTurno,
  turma,
  alunos_paginated,
  professores_paginated,
  grupos_paginated
}) {
  const instituicaoId = instituicao.id;
  const cursoId = cursoTutelado.id;
  const classeId = cursoClasse.id;
  const classe = cursoClasse.classe;
  const curso = cursoTutelado.curso;
  const turnos = cursoClasse.turnos || [];
  const [selectedTurnoId, setSelectedTurnoId] = useState(null);

  // Auto-select first turno
  useEffect(() => {
    if (turnos.length > 0 && !selectedTurnoId) {
      setSelectedTurnoId(turnos[0].id);
    }
  }, [turnos, selectedTurnoId]);

  // Estados de paginação separados
  const [alunosPage, setAlunosPage] = useState(alunos_paginated?.current_page || 1);
  const [professoresPage, setProfessoresPage] = useState(professores_paginated?.current_page || 1);
  const [gruposPage, setGruposPage] = useState(grupos_paginated?.current_page || 1);

  // Dados paginados
  const alunosData = alunos_paginated || { data: [], current_page: 1, last_page: 1, total: 0 };
  const professoresData = professores_paginated || { data: [], current_page: 1, last_page: 1, total: 0 };
  const gruposData = grupos_paginated || { data: [], current_page: 1, last_page: 1, total: 0 };

  // Função para mudar página dos alunos
  const handleAlunosPageChange = (newPage) => {
    if (newPage >= 1 && newPage <= alunosData.last_page) {
      setAlunosPage(newPage);
      router.get(
        window.location.pathname,
        { 
          ...router.params,
          page_alunos: newPage,
          page_professores: professoresPage,
          page_grupos: gruposPage
        },
        { preserveState: true, preserveScroll: true }
      );
    }
  };

  // Função para mudar página dos professores
  const handleProfessoresPageChange = (newPage) => {
    if (newPage >= 1 && newPage <= professoresData.last_page) {
      setProfessoresPage(newPage);
      router.get(
        window.location.pathname,
        { 
          ...router.params,
          page_alunos: alunosPage,
          page_professores: newPage,
          page_grupos: gruposPage
        },
        { preserveState: true, preserveScroll: true }
      );
    }
  };

  // Função para mudar página dos grupos PAP
  const handleGruposPageChange = (newPage) => {
    if (newPage >= 1 && newPage <= gruposData.last_page) {
      setGruposPage(newPage);
      router.get(
        window.location.pathname,
        { 
          ...router.params,
          page_alunos: alunosPage,
          page_professores: professoresPage,
          page_grupos: newPage
        },
        { preserveState: true, preserveScroll: true }
      );
    }
  };

  return (
    <div className="mx-auto w-full max-w-6xl space-y-4 p-6">
      {/* Header */}
      <Card className="gap-0">
        <CardHeader>
          <CardTitle className="text-xl">
            {turma.nome} - {cursoClasseTurno.turno?.nome}
          </CardTitle>

          <CardDescription>
            {cursoClasse.classe?.nome} • {cursoTutelado.curso?.nome}
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
                  Voltar à classe
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                  onClick={() =>
                    router.visit(
                      `/instituicoes/${instituicaoId}/cursos-tutelados/${cursoTuteladoId}/classes/${cursoClasseId}/turnos/${cursoClasseTurnoId}/turmas/${turmaId}/edit`,
                    )
                  }
                >
                  Editar turma
                </DropdownMenuItem>
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
                      <Button asChild size="sm" disabled={!selectedTurnoId}>
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
                                          router.visit(`#`);
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

      <Tabs defaultValue="alunos" className="w-full">
        <TabsList>
          <TabsTrigger value="alunos">Alunos</TabsTrigger>
          <TabsTrigger value="professores">Professores</TabsTrigger>
          <TabsTrigger value="grupos">Grupos PAP</TabsTrigger>
        </TabsList>

        {/* Tab Alunos */}
        <TabsContent value="alunos" className="mt-4">
          <Card>
            <CardHeader className="border-b">
              <CardTitle className="flex items-center gap-2">
                <UsersIcon className="size-5 text-primary" />
                Alunos Matriculados
              </CardTitle>
              <CardDescription>
                Lista de alunos desta turma
              </CardDescription>
            </CardHeader>

            {alunosData.data?.length === 0 ? (
              <CardContent className="flex flex-1 items-center justify-center py-12">
                <EmptyState
                  variant="table"
                  icon={UsersIcon}
                  title="Nenhum aluno"
                  description="Esta turma ainda não tem alunos matriculados"
                />
              </CardContent>
            ) : (
              <>
                <CardContent className="p-0">
                  <Table>
                    <TableHeader>
                      <TableRow className="bg-muted/72">
                        <TableHead className="px-4">Nome</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead>Telefone</TableHead>
                        <TableHead className="px-4 text-right">
                          Acções
                        </TableHead>
                      </TableRow>
                    </TableHeader>

                    <TableBody>
                      {alunosData.data.map((aluno) => (
                        <TableRow key={aluno.id}>
                          <TableCell className="px-4 font-medium">
                            {aluno.nome}
                          </TableCell>
                          <TableCell>{aluno.email || '-'}</TableCell>
                          <TableCell>{aluno.telefone || '-'}</TableCell>
                          <TableCell className="px-4 text-right">
                            <DropdownMenu>
                              <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="icon" className="size-8">
                                  <MoreHorizontalIcon className="size-4" />
                                </Button>
                              </DropdownMenuTrigger>
                              <DropdownMenuContent align="end">
                                <DropdownMenuItem
                                  onClick={() =>
                                    router.visit(
                                      `/instituicoes/${instituicaoId}/cursos-tutelados/${cursoTuteladoId}/classes/${cursoClasseId}/turnos/${cursoClasseTurnoId}/turmas/${turmaId}/alunos/${aluno.id}`
                                    )
                                  }
                                >
                                  Ver detalhes
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem variant="destructive">
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
                    Página {alunosData.current_page} de {alunosData.last_page}
                  </span>

                  <Pagination>
                    <PaginationContent>
                      <PaginationItem>
                        <PaginationPrevious
                          onClick={() => handleAlunosPageChange(alunosData.current_page - 1)}
                          className={
                            alunosData.current_page === 1
                              ? 'pointer-events-none opacity-50'
                              : ''
                          }
                        />
                      </PaginationItem>
                      <PaginationItem>
                        <PaginationNext
                          onClick={() => handleAlunosPageChange(alunosData.current_page + 1)}
                          className={
                            alunosData.current_page === alunosData.last_page
                              ? 'pointer-events-none opacity-50'
                              : ''
                          }
                        />
                      </PaginationItem>
                    </PaginationContent>
                  </Pagination>
                </CardFooter>
              </>
            )}
          </Card>
        </TabsContent>

        {/* Tab Professores */}
        <TabsContent value="professores" className="mt-4">
          <Card>
            <CardHeader className="border-b">
              <CardTitle className="flex items-center gap-2">
                <GraduationCapIcon className="size-5 text-primary" />
                Professores
              </CardTitle>
              <CardDescription>
                Professores associados a esta turma
              </CardDescription>
              <CardAction>
                <Button asChild size="sm">
                  <Link
                    href={`/instituicoes/${instituicaoId}/cursos-tutelados/${cursoTuteladoId}/classes/${cursoClasseId}/turnos/${cursoClasseTurnoId}/turmas/${turmaId}/professores/create`}
                  >
                    Adicionar Professor
                  </Link>
                </Button>
              </CardAction>
            </CardHeader>

            {professoresData.data?.length === 0 ? (
              <CardContent className="flex flex-1 items-center justify-center py-12">
                <EmptyState
                  variant="table"
                  icon={GraduationCapIcon}
                  title="Nenhum professor"
                  description="Esta turma ainda não tem professores associados"
                  action={{
                    label: 'Adicionar Professor',
                    href: `/instituicoes/${instituicaoId}/cursos-tutelados/${cursoTuteladoId}/classes/${cursoClasseId}/turnos/${cursoClasseTurnoId}/turmas/${turmaId}/professores/create`,
                    variant: 'outline',
                  }}
                />
              </CardContent>
            ) : (
              <>
                <CardContent className="p-0">
                  <Table>
                    <TableHeader>
                      <TableRow className="bg-muted/72">
                        <TableHead className="px-4">Professor</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead>Disciplina</TableHead>
                        <TableHead className="px-4 text-right">
                          Acções
                        </TableHead>
                      </TableRow>
                    </TableHeader>

                    <TableBody>
                      {professoresData.data.map((prof) => (
                        <TableRow key={prof.id}>
                          <TableCell className="px-4 font-medium">
                            {prof.professor?.nome}
                          </TableCell>
                          <TableCell>{prof.professor?.email || '-'}</TableCell>
                          <TableCell>{prof.disciplina?.nome}</TableCell>
                          <TableCell className="px-4 text-right">
                            <DropdownMenu>
                              <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="icon" className="size-8">
                                  <MoreHorizontalIcon className="size-4" />
                                </Button>
                              </DropdownMenuTrigger>
                              <DropdownMenuContent align="end">
                                <DropdownMenuItem>
                                  Editar
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem variant="destructive">
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
                    Página {professoresData.current_page} de {professoresData.last_page}
                  </span>

                  <Pagination>
                    <PaginationContent>
                      <PaginationItem>
                        <PaginationPrevious
                          onClick={() => handleProfessoresPageChange(professoresData.current_page - 1)}
                          className={
                            professoresData.current_page === 1
                              ? 'pointer-events-none opacity-50'
                              : ''
                          }
                        />
                      </PaginationItem>
                      <PaginationItem>
                        <PaginationNext
                          onClick={() => handleProfessoresPageChange(professoresData.current_page + 1)}
                          className={
                            professoresData.current_page === professoresData.last_page
                              ? 'pointer-events-none opacity-50'
                              : ''
                          }
                        />
                      </PaginationItem>
                    </PaginationContent>
                  </Pagination>
                </CardFooter>
              </>
            )}
          </Card>
        </TabsContent>

        {/* Tab Grupos PAP */}
        <TabsContent value="grupos" className="mt-4">
          <Card>
            <CardHeader className="border-b">
              <CardTitle className="flex items-center gap-2">
                <UsersRoundIcon className="size-5 text-primary" />
                Grupos PAP
              </CardTitle>
              <CardDescription>
                Grupos de Projeto de Aptidão Profissional
              </CardDescription>
              <CardAction>
                <Button asChild size="sm">
                  <Link
                    href={`/instituicoes/${instituicaoId}/cursos-tutelados/${cursoTuteladoId}/classes/${cursoClasseId}/turnos/${cursoClasseTurnoId}/turmas/${turmaId}/grupos/create`}
                  >
                    Criar Grupo
                  </Link>
                </Button>
              </CardAction>
            </CardHeader>

            {gruposData.data?.length === 0 ? (
              <CardContent className="flex flex-1 items-center justify-center py-12">
                <EmptyState
                  variant="table"
                  icon={UsersRoundIcon}
                  title="Nenhum grupo"
                  description="Esta turma ainda não tem grupos PAP criados"
                  action={{
                    label: 'Criar Grupo',
                    href: `/instituicoes/${instituicaoId}/cursos-tutelados/${cursoTuteladoId}/classes/${cursoClasseId}/turnos/${cursoClasseTurnoId}/turmas/${turmaId}/grupos/create`,
                    variant: 'outline',
                  }}
                />
              </CardContent>
            ) : (
              <>
                <CardContent className="p-0">
                  <Table>
                    <TableHeader>
                      <TableRow className="bg-muted/72">
                        <TableHead className="px-4">Nome do Grupo</TableHead>
                        <TableHead>Tema</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Nota Final</TableHead>
                        <TableHead className="px-4 text-right">
                          Acções
                        </TableHead>
                      </TableRow>
                    </TableHeader>

                    <TableBody>
                      {gruposData.data.map((grupo) => (
                        <TableRow key={grupo.id}>
                          <TableCell className="px-4 font-medium">
                            {grupo.nome_grupo}
                          </TableCell>
                          <TableCell>{grupo.tema_grupo || '-'}</TableCell>
                          <TableCell>
                            <Badge variant={grupo.status === 'aprovado' ? 'success' : grupo.status === 'em_andamento' ? 'warning' : 'secondary'}>
                              {grupo.status || 'Pendente'}
                            </Badge>
                          </TableCell>
                          <TableCell>{grupo.nota_final || '-'}</TableCell>
                          <TableCell className="px-4 text-right">
                            <DropdownMenu>
                              <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="icon" className="size-8">
                                  <MoreHorizontalIcon className="size-4" />
                                </Button>
                              </DropdownMenuTrigger>
                              <DropdownMenuContent align="end">
                                <DropdownMenuItem
                                  onClick={() =>
                                    router.visit(
                                      `/instituicoes/${instituicaoId}/cursos-tutelados/${cursoTuteladoId}/classes/${cursoClasseId}/turnos/${cursoClasseTurnoId}/turmas/${turmaId}/grupos/${grupo.id}`
                                    )
                                  }
                                >
                                  Ver detalhes
                                </DropdownMenuItem>
                                <DropdownMenuItem>Editar</DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem variant="destructive">
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
                    Página {gruposData.current_page} de {gruposData.last_page}
                  </span>

                  <Pagination>
                    <PaginationContent>
                      <PaginationItem>
                        <PaginationPrevious
                          onClick={() => handleGruposPageChange(gruposData.current_page - 1)}
                          className={
                            gruposData.current_page === 1
                              ? 'pointer-events-none opacity-50'
                              : ''
                          }
                        />
                      </PaginationItem>
                      <PaginationItem>
                        <PaginationNext
                          onClick={() => handleGruposPageChange(gruposData.current_page + 1)}
                          className={
                            gruposData.current_page === gruposData.last_page
                              ? 'pointer-events-none opacity-50'
                              : ''
                          }
                        />
                      </PaginationItem>
                    </PaginationContent>
                  </Pagination>
                </CardFooter>
              </>
            )}
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}