import { Link } from '@inertiajs/react';
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
import { create } from '@/actions/App/Http/Controllers/CursoTuteladoProfessorController';
import TablePagination from '@/components/table-pagination';

export function TabProfessores({
  professores,
  pagination = {},
  onPageChange,
  deleteProfessor,
  instituicaoId,
  cursoTuteladoId,
}) {
  const isEmpty = !professores.data || professores.data.length === 0;

  return (
    <Card className="gap-0">
      <CardHeader className="border-b">
        <CardTitle>Professores</CardTitle>
        <CardDescription>Professores associados a este curso</CardDescription>
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
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={BookOpenIcon}
            title="Nenhum professor associado"
            description="Comece adicionando professores ao curso"
            action={{
              label: 'Adicionar Professor',
              href: create({
                instituicao: instituicaoId,
                cursoTutelado: cursoTuteladoId,
              }).url,
              variant: 'outline',
            }}
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead>Tipo</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {professores.data.map((professor) => (
                <TableRow key={professor.id}>
                  <TableCell className="px-4 font-medium">
                    {professor.nome}
                  </TableCell>
                  <TableCell>
                    {professor.tipo ?? (
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
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                          variant="destructive"
                          onClick={() => deleteProfessor(professor.id)}
                        >
                          Remover do curso
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
