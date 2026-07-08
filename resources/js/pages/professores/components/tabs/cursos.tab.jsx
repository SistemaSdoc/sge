import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { EmptyState } from '@/components/empty-state';
import { BookOpenIcon, LayersIcon, MoreHorizontalIcon } from 'lucide-react';
import { show } from '@/actions/App/Http/Controllers/CursosController';
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
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination';
import { router } from '@inertiajs/react';

export function CursosTable({ cursos }) {
  const isEmpty = !cursos || cursos.length === 0;

  return (
    <Card className="gap-0">
      <CardHeader className="border-b">
        <CardTitle>Cursos</CardTitle>
        <CardDescription>Cursos associados a este professor</CardDescription>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={LayersIcon}
            title="Nenhum curso associado"
            description="Comece adicionando cursos a este professor"
            /*action={{
              label: 'Adicionar Curso',
              href: '/cursos/create',
              variant: 'outline',
            }}*/
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                {/*<TableHead className="px-4">Telefone</TableHead>*/}
                {/* <TableHead className="px-4 text-right">Acções</TableHead> */}
              </TableRow>
            </TableHeader>

            <TableBody>
              {cursos.map((curso) => (
                <TableRow
                  key={curso.id}
                  className="hover:cursor-pointer"
                  onClick={() => router.visit(show(curso.id).url)}
                >
                  <TableCell className="px-4 font-medium">
                    {curso.nome}
                  </TableCell>

                   {/*<TableCell className="px-4 font-medium">
                    {curso.telefone}
                  </TableCell>*/}

                  {/*<TableCell className="px-4 text-right">
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="icon" className="size-8">
                          <MoreHorizontalIcon />
                          <span className="sr-only">Open menu</span>
                        </Button>
                      </DropdownMenuTrigger>

                       <DropdownMenuContent align="end">
                        <DropdownMenuItem
                          variant="destructive"
                          onClick={(e) => {
                            e.stopPropagation();
                            excluir(curso.id);
                          }}
                        >
                          Remover
                        </DropdownMenuItem>
                      </DropdownMenuContent> 
                    </DropdownMenu>  
                  </TableCell> */}
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
