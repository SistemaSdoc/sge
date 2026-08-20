import { Link, router } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { Minus, MoreHorizontalIcon, BookIcon } from 'lucide-react';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
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
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { EmptyState } from '@/components/empty-state';
import {
  create,
  show,
  edit,
} from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoController';
import TablePagination from '@/components/table-pagination';

export function TabContentCursos({
  data,
  instituicaoId,
  deleteFn,
  pagination = {},
  onPageChange,
  can = {},
}) {
  const isEmpty = !data || data.length === 0;
  const canCreate = Boolean(can.create_curso || can.create);

  return (
    <Card className="mx-auto w-full max-w-7xl gap-0">
      <CardHeader className="border-b">
        <CardTitle>Cursos</CardTitle>
        <CardDescription>
          Cursos lecionados por esta instituição
        </CardDescription>
        {canCreate && (
          <CardAction>
            <Button asChild>
              <Link href={create(instituicaoId).url}>Adicionar</Link>
            </Button>
          </CardAction>
        )}
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={BookIcon}
            title="Nenhum curso cadastrado"
            description="Comece adicionando o primeiro curso à instituição"
            action={
              canCreate
                ? {
                    label: 'Adicionar Curso',
                    href: create(instituicaoId).url,
                    variant: 'outline',
                  }
                : undefined
            }
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead>Tutelado por</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {data.map((curso) => (
                <TableRow
                  key={curso.id}
                  className={
                    curso.can?.view ? 'hover:cursor-pointer' : 'opacity-70'
                  }
                  aria-disabled={!curso.can?.view}
                  onClick={() => {
                    if (curso.can?.view) {
                      router.visit(
                        show({
                          instituicao: instituicaoId,
                          cursoTutelado: curso?.id,
                        }).url,
                      );
                    }
                  }}
                >
                  <TableCell className="px-4 font-medium">
                    {curso.nome}
                  </TableCell>
                  <TableCell>
                    {curso.instituicao_tutora ? (
                      curso.instituicao_tutora
                    ) : (
                      <Minus size={15} className="text-muted-foreground" />
                    )}
                  </TableCell>
                  <TableCell className="px-4 text-right">
                    {(curso.can?.update || curso.can?.delete) && (
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
                          {curso.can?.update && (
                            <DropdownMenuItem
                              onClick={(e) => {
                                e.stopPropagation();
                                router.visit(
                                  edit({
                                    instituicao: instituicaoId,
                                    cursoTutelado: curso.id,
                                  }).url,
                                );
                              }}
                            >
                              Editar
                            </DropdownMenuItem>
                          )}

                          {curso.can?.update && curso.can?.delete && (
                            <DropdownMenuSeparator />
                          )}

                          {curso.can?.delete && (
                            <DropdownMenuItem
                              variant="destructive"
                              onClick={(e) => {
                                e.stopPropagation();
                                deleteFn(curso.id);
                              }}
                            >
                              Remover Curso
                            </DropdownMenuItem>
                          )}
                        </DropdownMenuContent>
                      </DropdownMenu>
                    )}
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </CardContent>

      <TablePagination pagination={pagination} onPageChange={onPageChange} />
    </Card>
  );
}
