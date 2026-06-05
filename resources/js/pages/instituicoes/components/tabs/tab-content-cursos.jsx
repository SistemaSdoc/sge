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

export function TabContentCursos({ data, instituicaoId }) {
  const isEmpty = !data || data.length === 0;

  return (
    <Card className="mx-auto w-full max-w-7xl gap-0">
      <CardHeader className="border-b">
        <CardTitle>Cursos</CardTitle>
        <CardDescription>
          Cursos lecionados por esta instituição
        </CardDescription>
        <CardAction>
          <Button asChild>
            <Link href={`/instituicoes/${instituicaoId}/cursos-tutelados/create`}>
              Adicionar
            </Link>
          </Button>
        </CardAction>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={BookIcon}
            title="Nenhum curso cadastrado"
            description="Comece adicionando o primeiro curso à instituição"
            action={{
              label: 'Adicionar Curso',
              href: `/instituicoes/${instituicaoId}/cursos-tutelados/create`,
              variant: 'outline',
            }}
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead>Nome</TableHead>
                <TableHead>Tutelado por</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {data.map((curso) => (
                <TableRow
                  key={curso.id}
                  className="hover:cursor-pointer"
                  onClick={() => router.visit(`/instituicoes/${instituicaoId}/cursos-tutelados/${curso.id}`)}
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
                            router.visit(
                              `/instituicoes/${instituicaoId}/cursos-tutelados/${curso.id}/edit`,
                            );
                          }}
                        >
                          Editar
                        </DropdownMenuItem>

                        <DropdownMenuSeparator />

                        <DropdownMenuItem
                          variant="destructive"
                          onClick={(e) => {
                            e.stopPropagation();
                            router.visit(
                              `/instituicoes/${instituicaoId}/cursos-tutelados/${curso.id}`,
                            );
                          }}
                        >
                          Remover Curso
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
  );
}
