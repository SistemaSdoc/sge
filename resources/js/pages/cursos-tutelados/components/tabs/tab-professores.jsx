import { Link } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardAction,
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

import { Minus, BookOpenIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { create } from '@/actions/App/Http/Controllers/CursoTuteladoProfessorController';
import { show } from '@/actions/App/Http/Controllers/ProfessorController';

import TablePagination from '@/components/table-pagination';
import { useState } from 'react';
import EditProfessorModal from './edit-professor-modal';

export function TabProfessores({
  params,
  professores,
  pagination = {},
  onPageChange,
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
          <CardTitle>
            Professores ({params.cursoTutelado.contadores?.professores ?? 0})
          </CardTitle>
          <CardDescription>Professores associados a este curso</CardDescription>
          {can?.attachProfessor && (
            <CardAction>
              <Button asChild>
                <Link href={create({ ...params }).url}>Adicionar</Link>
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
                  href: create({ ...params }).url,
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
                          <div className="flex justify-end gap-2">
                            <Button
                              variant="outline"
                              size="xs"
                              className="text-[10px]"
                              onClick={(e) => {
                                e.stopPropagation();
                                setEditVinculo({
                                  ...professor,
                                  ...params,
                                });
                              }}
                            >
                              Editar do Curso
                            </Button>

                            <Button
                              variant="destructive"
                              size="xs"
                              className="text-[10px]"
                              onClick={(e) => {
                                e.stopPropagation();
                                deleteFn(professor.vinculo_id);
                              }}
                            >
                              Remover do curso
                            </Button>
                          </div>
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
          params={params}
          vinculo={editVinculo}
          open={!!editVinculo}
          onClose={() => setEditVinculo(null)}
        />
      )}
    </>
  );
}
