import { useState } from "react";
import { router } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
import {
  Card,
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
import { Minus, MoreHorizontalIcon, BookIcon } from "lucide-react";
import { EmptyState } from "@/components/empty-state";

export function TabDisciplinas({
  turma,
  instituicaoId,
  cursoTuteladoId,
  cursoClasseId,
  cursoClasseTurnoId,
}) {
  const turmaId = turma.id;

  // base para rotas nested
  const baseUrl = `/instituicoes/${instituicaoId}/cursos-tutelados/${cursoTuteladoId}/classes/${cursoClasseId}/turnos/${cursoClasseTurnoId}/turmas/${turmaId}`;

  // disciplinas vêm do relacionamento carregado no controller
  const disciplinas = turma.curso_classe_turno?.classe_turno_disciplinas ?? [];
  const isEmpty = disciplinas.length === 0;

  return (
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
                // professor atribuído a esta disciplina nesta turma
                const professor = turma.turma_disciplina_professor?.find(
                  (tdp) => tdp.classe_turno_disciplina_id === disciplina.id,
                )?.professor?.user;

                return (
                  <TableRow
                    key={disciplina.id}
                    className="hover:cursor-pointer"
                    onClick={() =>
                      router.visit(`${baseUrl}/disciplinas/${disciplina.id}/notas`)
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
                          <Button variant="ghost" size="icon" className="size-8">
                            <MoreHorizontalIcon />
                            <span className="sr-only">Abrir menu</span>
                          </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end">
                          <DropdownMenuItem
                            onClick={(e) => {
                              e.stopPropagation();
                              router.visit(`${baseUrl}/disciplinas/${disciplina.id}/notas`);
                            }}
                          >
                            Ver notas
                          </DropdownMenuItem>

                          <DropdownMenuSeparator />

                          <DropdownMenuItem
                            variant="destructive"
                            onClick={(e) => {
                              e.stopPropagation();
                              router.delete(`${baseUrl}/disciplinas/${disciplina.id}`);
                            }}
                          >
                            Remover
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
    </Card>
  );
}