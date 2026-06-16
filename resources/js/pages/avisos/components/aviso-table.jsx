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
import TablePagination from '@/components/table-pagination';

export default function avisoTable({
  avisos,
  deleteFn,
  pagination = {},
  onPageChange,
}) {
  const isEmpty = !avisos || avisos.length === 0;

  return (
    <Card className="mx-auto w-full max-w-7xl gap-0">
      <CardHeader className="border-b">
        <CardTitle>Avisos</CardTitle>
        <CardDescription>Lista de avisos cadastrados</CardDescription>
        <CardAction>
          <Button asChild>
            <Link href="/avisos/create">Adicionar</Link>
          </Button>
        </CardAction>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={LayersIcon}
            title="Nenhum aviso cadastrado"
            description="Comece adicionando a primeiro aviso à tabela"
            action={{
              label: 'Adicionar aviso',
              href: '/avisos/create',
              variant: 'outline',
            }}
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Titulo</TableHead>
                <TableHead className="px-4">Descrição</TableHead>
                <TableHead className="px-4">Estado</TableHead>
                <TableHead className="px-4">Destinatário</TableHead>
                <TableHead className="px-4">Data</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {avisos.map((aviso) => (
                <TableRow
                  key={aviso.id}
                  className="hover:avisor-pointer"
                  onClick={() => router.visit(`/avisos/${aviso.id}`)}
                >
                  <TableCell className="px-4 font-medium">
                    {aviso.titulo}
                  </TableCell>

                  <TableCell className="px-4 font-medium">
                    {aviso.descricao}
                  </TableCell>

                  <TableCell className="px-4 font-medium">
                    {aviso.ativo ? 'Ativo' : 'Inativo'}
                  </TableCell>

                  <TableCell className="px-4 font-medium">
                    {aviso.destinatario}
                  </TableCell>

                  <TableCell className="px-4 font-medium">
                    {aviso.data}
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
                            router.visit(`/avisos/${aviso.id}/edit`);
                          }}
                        >
                          Editar
                        </DropdownMenuItem>

                        <DropdownMenuSeparator />

                        <DropdownMenuItem
                          variant="destructive"
                          onClick={(e) => {
                            e.stopPropagation();
                            deleteFn(aviso.id);
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
