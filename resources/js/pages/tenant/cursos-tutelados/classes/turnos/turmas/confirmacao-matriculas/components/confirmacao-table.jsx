import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { ArrowRightCircle, MoreHorizontalIcon, UsersIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
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
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import TablePagination from '@/components/table-pagination';
import { ResultadoBadge } from '@/pages/tenant/pautas/components/pauta-table/resultado-badge';

export function ConfirmacaoTable({
  data,
  pagination = {},
  onPageChange,
  onConfirmar,
  turma,
}) {
  const isEmpty = !data || data.length === 0;

  return (
    <>
      <style>{`
        @keyframes confirmacao-chevron-move {
          0%, 100% { transform: translateX(-5px); }
          50% { transform: translateX(15px); }
        }
        .confirmacao-chevron {
          animation: confirmacao-chevron-move 1.2s ease-in-out infinite;
        }
      `}</style>

      <div className="mx-auto w-full max-w-7xl p-6">
        <Card className="gap-0">
          <CardHeader className="border-b">
            <CardTitle>Confirmação de matrículas</CardTitle>
            <CardDescription>
              Lista de alunos por confirmar a sua matrícula
            </CardDescription>

            <CardAction className="flex gap-3">
              <Field>
                <div className="flex gap-2">
                  <Input placeholder="Digite para pesquisar..." />
                  <Button variant="outline">Pesquisar</Button>
                </div>
              </Field>
            </CardAction>
          </CardHeader>

          <CardContent className="p-0!">
            {isEmpty ? (
              <EmptyState
                variant="table"
                icon={UsersIcon}
                title="Nenhuma aluno por confirmar matrícula"
                description="Não existem alunos por confirmar a sua matrícula."
              />
            ) : (
              <Table>
                <TableHeader>
                  <TableRow className="bg-muted/72">
                    <TableHead className="px-4">Nome</TableHead>
                    <TableHead className="px-4">Curso</TableHead>
                    <TableHead className="px-4">Turno</TableHead>
                    <TableHead className="px-4">Turma</TableHead>
                    <TableHead className="px-4">Status</TableHead>
                    <TableHead className="px-4 text-right">Acções</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {data?.map((aluno) => (
                    <TableRow
                      key={aluno.id}
                      className="hover:cursor-pointer"
                      onClick={() =>
                        router.visit(`/dashboard/alunos/${aluno.id}`)
                      }
                    >
                      <TableCell className="px-4 font-medium">
                        {aluno.nome}
                      </TableCell>
                      <TableCell className="px-4 font-medium">
                        {aluno.curso}
                      </TableCell>
                      <TableCell className="px-4 font-medium">
                        {aluno.turno}
                      </TableCell>
                      <TableCell className="px-4 font-medium">
                        {aluno.turma}
                      </TableCell>
                      <TableCell className="px-4 font-medium">
                        <ResultadoBadge resultado={aluno.status} />
                      </TableCell>
                        <TableCell className="px-4 text-right">
                          <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                              <Button
                                variant="ghost"
                                size="icon"
                                className="size-8"
                              >
                                <MoreHorizontalIcon />
                                <span className="sr-only">Abrir menu</span>
                              </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-auto">
                              <DropdownMenuItem
                                onClick={(e) => {
                                    onConfirmar(aluno, e);
                                }}
                              >
                                Confirmar matrícula
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

          <TablePagination
            pagination={pagination}
            onPageChange={onPageChange}
          />
        </Card>
      </div>
    </>
  );
}
