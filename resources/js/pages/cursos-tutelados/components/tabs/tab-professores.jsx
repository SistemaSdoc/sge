import { Link } from '@inertiajs/react';
import { router } from '@inertiajs/react';
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
import { MoreHorizontalIcon, Minus, BookOpenIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import CursoTuteladoProfessorController, {
  create,
  edit,
  destroy,
} from '@/actions/App/Http/Controllers/CursoTuteladoProfessorController';
import { show } from '@/actions/App/Http/Controllers/ProfessorController';

import TablePagination from '@/components/table-pagination';
import { useState } from 'react';
import EditProfessorModal from './edit-professor-modal';

export function TabProfessores({
  professores,
  pagination = {},
  onPageChange,
  deleteProfessor,
  instituicaoId,
  cursoTuteladoId,
  deleteFn,
  can = {},
}) {
  const [editVinculo, setEditVinculo] = useState(null);
  const isEmpty = !professores.data || professores.data.length === 0;
  const hasAnyAction = can?.update || can?.delete;

  return (
    <>
      <Card className="gap-0">
        <CardHeader className="border-b">
          <CardTitle>Professores</CardTitle>
          <CardDescription>Professores associados a este curso</CardDescription>
          {can?.attachProfessor && (
            <CardAction>
              <Button asChild>
                <Link
                  href={
                    create({
                      instituicao: instituicaoId,
                      cursoTutelado: cursoTuteladoId,
                    }).url
                  }
                >
                  Adicionar
                </Link>
              </Button>
            </CardAction>
          )}
        </CardHeader>

        <CardContent className="p-0!">
          {isEmpty ? (
            <EmptyState
              variant="table"
              icon={BookOpenIcon}
              title="Nenhum professor associado"
              description="Comece adicionando professores ao curso"
              action={
                can?.attachProfessor && {
                  label: 'Adicionar Professor',
                  href: create({
                    instituicao: instituicaoId,
                    cursoTutelado: cursoTuteladoId,
                  }).url,
                  variant: 'outline',
                }
              }
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/72">
                  <TableHead className="px-4">Nome</TableHead>
                  <TableHead>Tipo</TableHead>
                  {hasAnyAction && (
                    <TableHead className="px-4 text-right">Acções</TableHead>
                  )}
                </TableRow>
              </TableHeader>
              <TableBody>
                {professores.data.map((professor) => (
                  <TableRow
                    key={professor.id}
                    className="hover:cursor-pointer"
                    onClick={() =>
                      router.visit(show({ professor: professor.id }).url)
                    }
                  >
                    <TableCell className="px-4 font-medium">
                      {professor.nome}
                    </TableCell>
                    <TableCell>
                      {professor.tipo ?? (
                        <Minus size={15} className="text-muted-foreground" />
                      )}
                    </TableCell>
                    {hasAnyAction && (
                    <TableCell className="px-4 text-right">
                      {(professor.can?.update || professor.can?.delete) && (
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

                        <DropdownMenuContent className="w-auto" align="end">
                          <DropdownMenuSeparator />
                          {professor.can?.update && (
                            <DropdownMenuItem
                              onClick={(e) => {
                                e.stopPropagation();
                                setEditVinculo({
                                  ...professor,
                                  instituicaoId,
                                  cursoTuteladoId,
                                });
                              }}
                            >
                              Editar do Curso
                            </DropdownMenuItem>
                          )}
                          {professor.can?.update && professor.can?.delete && (
                              <DropdownMenuSeparator />
                            )}
                          {professor.can?.delete && (
                            <DropdownMenuItem
                              variant="destructive"
                              onClick={(e) => {
                                e.stopPropagation();
                                deleteFn(professor.vinculo_id);
                              }}
                            >
                              Remover do curso
                            </DropdownMenuItem>
                          )}
                        </DropdownMenuContent>
                      </DropdownMenu>
                      )}
                    </TableCell>
                    )}
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>

        <TablePagination pagination={pagination} onPageChange={onPageChange} />
      </Card>

      {editVinculo && (
        <EditProfessorModal
          vinculo={editVinculo}
          open={!!editVinculo}
          onClose={() => setEditVinculo(null)}
        />
      )}
    </>
  );
}