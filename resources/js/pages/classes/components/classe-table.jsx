import { Link, router } from '@inertiajs/react';
import { MoreHorizontalIcon, LayersIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
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

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  show,
  create,
  edit
} from '@/actions/App/Http/Controllers/ClasseController';

export function ClasseTable({
  classes,
  deleteFn,
  pagination = {},
  onPageChange,
}) {
  const lista = classes?.data ?? [];
  const isEmpty = lista.length === 0;

  return (
    <Card className="mx-auto w-full max-w-7xl gap-0">
      <CardHeader className="border-b">
        <CardTitle>Classes</CardTitle>
        <CardDescription>Lista de classes cadastradas</CardDescription>
        <CardAction>
          <Button asChild>
            <Link href={create().url}>Adicionar</Link>
          </Button>
        </CardAction>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={LayersIcon}
            title="Nenhuma classe cadastrada"
            description="Comece adicionando a primeira classe à tabela"
            action={{
              label: 'Adicionar Classe',
              href: create().url,
              variant: 'outline',
            }}
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              {lista.map((classe) => (
                <TableRow
                  key={classe.id}
                  className="hover:cursor-pointer"
                  onClick={() => router.visit(show(classe.id).url)}
                >
                  <TableCell className="px-4 font-medium">
                    {classe.nome}
                  </TableCell>

                  <TableCell className="px-4 text-right">
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="icon" className="size-8">
                          <MoreHorizontalIcon />
                          <span className="sr-only">Open menu</span>
                        </Button>
                      </DropdownMenuTrigger>

                      <DropdownMenuContent align="end">
                        <DropdownMenuItem
                          onClick={(e) => {
                            e.stopPropagation();
                            router.visit(edit(classe.id).url);
                          }}
                        >
                          Editar
                        </DropdownMenuItem>

                        <DropdownMenuSeparator />

                        <DropdownMenuItem
                          variant="destructive"
                          onClick={(e) => {
                            e.stopPropagation();
                            deleteFn(classe.id);
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

      {/* [CORRIGIDO] CardFooter fora do CardContent */}
      {pagination?.current_page && (
        <CardFooter className="justify-between">
          <span className="text-muted-foreground">
            Página {pagination.current_page} de {pagination.last_page}
          </span>

          <Pagination>
            <PaginationContent>
              {/* [ALTERADO] PaginationPrevious → PaginationLink com controlo manual */}
              <PaginationItem>
                <PaginationLink
                  onClick={() =>
                    pagination.current_page > 1 &&
                    onPageChange(pagination.current_page - 1)
                  }
                  className={
                    pagination.current_page === 1
                      ? 'pointer-events-none opacity-50'
                      : 'cursor-pointer'
                  }
                >
                  Anterior
                </PaginationLink>
              </PaginationItem>

              {/* [ALTERADO] PaginationNext → PaginationLink com controlo manual */}
              <PaginationItem>
                <PaginationLink
                  onClick={() =>
                    pagination.current_page < pagination.last_page &&
                    onPageChange(pagination.current_page + 1)
                  }
                  className={
                    pagination.current_page === pagination.last_page
                      ? 'pointer-events-none opacity-50'
                      : 'cursor-pointer'
                  }
                >
                  Próximo
                </PaginationLink>
              </PaginationItem>
            </PaginationContent>
          </Pagination>
        </CardFooter>
      )}
    </Card>
  );
}
