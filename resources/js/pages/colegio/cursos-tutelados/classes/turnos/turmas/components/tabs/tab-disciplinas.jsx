import { Link, router } from '@inertiajs/react';
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

import { Minus, BookIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { useDialog } from '@/hooks/use-dialog';
import { create as createDisciplina } from '@/actions/App/Http/Controllers/ClasseTurnoDisciplinaController';
import { index } from '@/actions/App/Http/Controllers/Colegios/NotaDisciplinaController';
import TablePagination from '@/components/table-pagination';
import { toast } from 'sonner';

export function TabDisciplinas({
  disciplinas,
  params,
  pagination,
  onPageChange,
  redirectTo, // <-- adiciona
  can = {},
}) {
  const isEmpty = disciplinas.length === 0;
  const canCreate = Boolean(can.create);


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
              </TableRow>
            </TableHeader>

            <TableBody>
              {disciplinas.map((disciplina) => {
                return (
                  <TableRow
                    key={disciplina.id}
                    aria-disabled={!disciplina?.can?.view}
                    className="hover:cursor-pointer aria-disabled:cursor-not-allowed aria-disabled:opacity-70 aria-disabled:hover:bg-transparent"
                    onClick={() => {
                      if (!disciplina.professor) {
                        toast.warning(
                          'Esta disciplina ainda não tem professor atribuído.',
                        );
                        return;
                      }

                      if (disciplina?.can?.view) {
                        router.visit(
                          index({
                            ...params,
                            classeTurnoDisciplina: disciplina.id,
                          }).url,
                        );
                      }
                    }}
                  >
                    <TableCell className="px-4 font-medium">
                      {disciplina?.nome}
                    </TableCell>

                    <TableCell>
                      {disciplina.professor?.nome ?? (
                        <Minus size={15} className="text-muted-foreground" />
                      )}
                    </TableCell>

                   
                  </TableRow>
                );
              })}
            </TableBody>
          </Table>
        )}
      </CardContent>

      <TablePagination pagination={pagination} onPageChange={onPageChange} />
    </Card>
  );
}
