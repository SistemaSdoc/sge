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
            <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
              {/* Título e Descrição */}
              <div className="min-w-0 space-y-1">
                <CardTitle>Confirmação de matrículas</CardTitle>
                <CardDescription>
                  Lista de alunos por confirmar a sua matrícula
                </CardDescription>
              </div>

              {/* Search */}
              <CardAction className="w-full shrink-0 md:w-auto">
                <Field>
                  <div className="flex w-full flex-col gap-2 md:w-auto md:flex-row">
                    <Input
                      placeholder="Digite para pesquisar..."
                      className="w-full md:w-auto"
                    />
                    <Button variant="outline" className="w-full md:w-auto">
                      Pesquisar
                    </Button>
                  </div>
                </Field>
              </CardAction>
            </div>
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
                      <TableCell
                        className="px-4 text-right"
                        onClick={(e) => e.stopPropagation()}
                        onPointerDown={(e) => e.stopPropagation()}
                      >
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
                            {aluno.aguarda_resultado_recurso ? (
                              <DropdownMenuItem disabled>
                                Aguardar resultado do recurso
                              </DropdownMenuItem>
                            ) : (
                              <DropdownMenuItem
                                disabled={!aluno.can?.confirmar_matricula}
                                onClick={(e) => {
                                  onConfirmar(aluno, e);
                                }}
                              >
                                {aluno.status === 'incompleto'
                                  ? 'Notas incompletas'
                                  : 'Confirmar matrícula'}
                              </DropdownMenuItem>
                            )}
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
