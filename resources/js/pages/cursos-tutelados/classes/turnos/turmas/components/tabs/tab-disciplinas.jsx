import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardFooter,
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
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination';
import { Minus, MoreHorizontalIcon, BookIcon, Clock } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { HorariosDialog } from '../horarios/horarios-dialog';
import { create as createDisciplina } from '@/actions/App/Http/Controllers/ClasseTurnoDisciplinaController';
import { index } from '@/actions/App/Http/Controllers/NotaController';
import { create as createProfessor } from '@/actions/App/Http/Controllers/InstituicaoCurso/TurmaDisciplinaProfessorController';

export function TabDisciplinas({
  turma,
  instituicaoId,
  cursoTuteladoId,
  cursoClasseId,
  cursoClasseTurnoId,
}) {
  const turmaId = turma.id;

  // The backend returns snake_case properties: classe_turno_disciplinas
  const disciplinas = turma.curso_classe_turno?.classe_turno_disciplinas ?? [];
  const isEmpty = disciplinas.length === 0;

  const [horariosDialogOpen, setHorariosDialogOpen] = useState(false);
  const [disciplinaSelectedParaHorario, setDisciplinaSelectedParaHorario] =
    useState(null);

  const abrirHorariosDialog = (disciplina, e) => {
    e.stopPropagation();
    setDisciplinaSelectedParaHorario(disciplina);
    setHorariosDialogOpen(true);
  };

  const fecharHorariosDialog = () => {
    setHorariosDialogOpen(false);
    setDisciplinaSelectedParaHorario(null);
  };

  return (
    <>
      <Card className="gap-0">
        <CardHeader className="border-b">
          <CardTitle>Disciplinas</CardTitle>
          <CardDescription>Disciplinas lecionadas nesta turma</CardDescription>
        </CardHeader>
        <CardContent className="p-0!">
          {isEmpty ? (
            <EmptyState
              variant="table"
              icon={BookIcon}
              title="Nenhuma disciplina nesta turma"
              description="Comece adicionando disciplinas"
              action={{
                label: 'Adicionar Disciplina',
                href: createDisciplina({
                  instituicao: instituicaoId,
                  cursoTutelado: cursoTuteladoId,
                  cursoClasse: cursoClasseId,
                  cursoClasseTurno: cursoClasseTurnoId,
                }).url,
                variant: 'outline',
              }}
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/72">
                  <TableHead className="px-4">Nome</TableHead>
                  <TableHead>Professor</TableHead>
                  <TableHead className="px-4 text-right">Acções</TableHead>
                </TableRow>
              </TableHeader>

              <TableBody>
                {disciplinas.map((disciplina) => {
                  // Backend returns snake_case properties
                  const professor = turma.turma_disciplina_professor?.find(
                    (tdp) => tdp.classe_turno_disciplina_id === disciplina.id,
                  )?.professor?.user;

                  return (
                    <TableRow
                      key={disciplina.id}
                      className="hover:cursor-pointer"
                      onClick={() =>
                        router.visit(
                          index({
                            instituicao: instituicaoId,
                            cursoTutelado: cursoTuteladoId,
                            cursoClasse: cursoClasseId,
                            cursoClasseTurno: cursoClasseTurnoId,
                            turma: turmaId,
                            classeTurnoDisciplina: disciplina.id,
                          }).url, // ← estava a faltar isto
                        )
                      }
                    >
                      <TableCell className="px-4 font-medium">
                        {disciplina.disciplina?.nome}
                      </TableCell>
                      <TableCell>
                        {professor?.nome ?? (
                          <Minus size={15} className="text-muted-foreground" />
                        )}
                      </TableCell>
                      <TableCell className="px-4 text-right">
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button
                              variant="ghost"
                              size="icon"
                              className="size-8"
                            >
                              <MoreHorizontalIcon />
                              <span className="sr-only">Open menu</span>
                            </Button>
                          </DropdownMenuTrigger>

                          <DropdownMenuContent align="end">
                            <DropdownMenuItem
                              onClick={(e) => {
                                e.stopPropagation();
                                router.visit(
                                  createProfessor({
                                    instituicao: instituicaoId,
                                    cursoTutelado: cursoTuteladoId,
                                    cursoClasse: cursoClasseId,
                                    cursoClasseTurno: cursoClasseTurnoId,
                                    turma: turmaId,
                                    classeTurnoDisciplina: disciplina.id,
                                  }).url,
                                );
                              }}
                            >
                              Definir professor
                            </DropdownMenuItem>

                            <DropdownMenuItem
                              onClick={(e) =>
                                abrirHorariosDialog(disciplina, e)
                              }
                            >
                              <Clock className="mr-2 size-4" />
                              Definir horários
                            </DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </TableCell>
                    </TableRow>
                  );
                })}
              </TableBody>
            </Table>
          )}
        </CardContent>

        {!isEmpty && (
          <CardFooter className="justify-between">
            <span className="text-muted-foreground">Página 1 de 4</span>

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
        )}
      </Card>

      {disciplinaSelectedParaHorario && (
        <HorariosDialog
          isOpen={horariosDialogOpen}
          onClose={fecharHorariosDialog}
          disciplina={disciplinaSelectedParaHorario}
          instituicaoId={instituicaoId}
          cursoTuteladoId={cursoTuteladoId}
          classeId={classeId}
          turnoId={turnoId}
          turmaId={turmaId}
          onSuccess={() => {
            // Refetch dos dados se necessário
            console.log('Horários salvos com sucesso!');
          }}
        />
      )}
    </>
  );
}
