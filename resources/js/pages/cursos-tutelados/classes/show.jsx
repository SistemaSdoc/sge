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
  const cursoTuteladoId = cursoTutelado.id;
  const cursoClasseId = cursoClasse.id;
  const cursoClasseTurnoId = cursoClasseTurno.id;
  const turmaId = turma.id;

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
                      `/instituicoes/${instituicaoId}/cursos-tutelados/${cursoTuteladoId}/classes/${cursoClasseId}`,
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

      {/* Contadores */}
      <div className="grid grid-cols-3 gap-4">
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-2">
              <UsersIcon className="size-4 text-muted-foreground" />
              <p className="text-sm text-muted-foreground">Alunos</p>
            </div>
            <h2 className="text-xl font-semibold">
              {alunosData.total}
            </h2>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-2">
              <GraduationCapIcon className="size-4 text-muted-foreground" />
              <p className="text-sm text-muted-foreground">Professores</p>
            </div>
            <h2 className="text-xl font-semibold">
              {professoresData.total}
            </h2>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-2">
              <UsersRoundIcon className="size-4 text-muted-foreground" />
              <p className="text-sm text-muted-foreground">Grupos PAP</p>
            </div>
            <h2 className="text-xl font-semibold">
              {gruposData.total}
            </h2>
          </CardContent>
        </Card>
      </div>

      {/* Tabs */}
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