import { Link, router } from '@inertiajs/react';
import { BuildingIcon } from 'lucide-react';
import { MoreHorizontalIcon } from 'lucide-react';
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
  edit,
  show,
  create,
} from '@/actions/App/Http/Controllers/InstituicaoController';
import TablePagination from '@/components/table-pagination';

export function InstituicaoTable({
  instituicoes,
  deleteFn,
  pagination = {},
  onPageChange,
}) {
  const isEmpty = !instituicoes || instituicoes.length === 0;

  return (
    <Card className="gap-0">
      <CardHeader className="border-b">
        <CardTitle>Instituições</CardTitle>
        <CardDescription>Lista de intituições cadastradas</CardDescription>
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
            icon={BuildingIcon}
            title="Nenhuma instituição cadastrada"
            description="Comece adicionando a primeira instituição à tabela"
            action={{
              label: 'Adicionar Instituição',
              href: create().url,
              variant: 'outline',
            }}
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Sigla</TableHead>
                <TableHead>Nome</TableHead>
                <TableHead>Tipo</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              {instituicoes.map((instituicao) => (
                <TableRow
                  key={instituicao.id}
                  className="hover:cursor-pointer"
                  onClick={() => router.visit(show(instituicao.id).url)}
                >
                  <TableCell className="px-4 font-medium">
                    {instituicao.sigla}
                  </TableCell>
                  <TableCell>{instituicao.nome}</TableCell>
                  <TableCell>{instituicao.tipo}</TableCell>
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
                            router.visit(edit(instituicao.id).url);
                          }}
                        >
                          Editar
                        </DropdownMenuItem>

                        <DropdownMenuSeparator />

                        <DropdownMenuItem
                          variant="destructive"
                          onClick={(e) => {
                            e.stopPropagation();
                            deleteFn(instituicao.id);
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

      <TablePagination pagination={pagination} onPageChange={onPageChange} />
    </Card>
  );
}
