"use client";
import { useState } from "react";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from "@/components/ui/pagination";
import { Minus, MoreHorizontalIcon, BookIcon, Clock } from "lucide-react";
import { EmptyState } from "@/components/empty-state";
import { HorariosDialog } from "../horarios/horarios-dialog";
import Link from "next/link";

export function TabDisciplinas({
  data,
  instituicaoId,
  cursoId,
  classeId,
  turnoId,
  turmaId,
  removerProfessorFn,
}) {
  const router = useRouter();
  const [horariosDialogOpen, setHorariosDialogOpen] = useState(false);
  const [disciplinaSelectedParaHorario, setDisciplinaSelectedParaHorario] =
    useState(null);

  const isEmpty = !data?.disciplinas || data.disciplinas.length === 0;

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
                label: "Adicionar Disciplina",
                href: `/dashboard/instituicoes/${instituicaoId}/cursos/${cursoId}/turmas/${turmaId}/disciplinas/create`,
                variant: "outline",
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
                {data?.disciplinas.map((disciplina) => (
                  <TableRow
                    key={disciplina?.id}
                    className="hover:cursor-pointer"
                    onClick={() =>
                      router.push(
                        `/dashboard/instituicoes/${instituicaoId}/cursos/${cursoId}/classes/${classeId}/turnos/${turnoId}/turmas/${turmaId}/disciplinas/${disciplina.id}/notas`,
                      )
                    }
                  >
                    <TableCell className="px-4 font-medium">
                      {disciplina.nome}
                    </TableCell>
                    <TableCell>
                      {disciplina?.professor?.nome ?? (
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
                              router.push(
                                `/dashboard/instituicoes/${instituicaoId}/cursos/${cursoId}/classes/${classeId}/turnos/${turnoId}/turmas/${turmaId}/disciplinas/${disciplina.id}/professores/create`,
                              );
                            }}
                          >
                            Definir professor
                          </DropdownMenuItem>

                          <DropdownMenuItem
                            onClick={(e) => abrirHorariosDialog(disciplina, e)}
                          >
                            <Clock className="mr-2 size-4" />
                            Definir horários
                          </DropdownMenuItem>

                          <DropdownMenuSeparator />

                          <DropdownMenuItem
                            variant="destructive"
                            onClick={(e) => {
                              e.stopPropagation();
                              removerProfessorFn(disciplina.id);
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
          cursoId={cursoId}
          classeId={classeId}
          turnoId={turnoId}
          turmaId={turmaId}
          onSuccess={() => {
            // Refetch dos dados se necessário
            console.log("Horários salvos com sucesso!");
          }}
        />
      )}
    </>
  );
}
