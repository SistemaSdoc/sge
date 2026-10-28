import { router } from '@inertiajs/react';
import {
  LayersIcon,
  MoreHorizontalIcon,
  XCircleIcon,
} from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
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
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import TablePagination from '@/components/table-pagination';

export default function PrazosTable({
  prazos = [],
  pagination = {},
  onPageChange,
  onFechar,
  onCreate,
}) {
  const isEmpty = prazos.length === 0;

  return (
    <Card className="mx-auto w-full max-w-7xl gap-0">
      <CardHeader className="flex flex-col gap-3 border-b md:flex-row md:items-center md:justify-between">
        <div className="min-w-0 md:flex-1">
          <CardTitle>Prazos de Provas</CardTitle>
          <CardDescription>
            Lista de prazos criados para submissão de provas.
          </CardDescription>
        </div>
<CardAction className="w-full md:w-auto">
  <Button onClick={onCreate} className="w-full md:w-auto">
    Novo Prazo
  </Button>
</CardAction>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={LayersIcon}
            title="Nenhum prazo criado"
            description="Comece por criar o primeiro prazo de provas."
            action={{
              label: 'Criar prazo',
              onClick: onCreate,
              variant: 'outline',
            }}
          />
        ) : (
          <div className="w-full overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/72">
                  <TableHead className="min-w-48 px-4">Título</TableHead>
                  <TableHead className="min-w-40 px-4">Disciplina</TableHead>
                  <TableHead className="min-w-32 px-4">Classe</TableHead>
                  <TableHead className="min-w-36 px-4 text-center">
                    Data Limite
                  </TableHead>
                  <TableHead className="min-w-32 px-4 text-center">
                    Status
                  </TableHead>
                  <TableHead className="min-w-32 px-4 text-center">
                    Submissões
                  </TableHead>
                  <TableHead className="min-w-20 px-4 text-right">
                    Ações
                  </TableHead>
                </TableRow>
              </TableHeader>

              <TableBody>
                {prazos.map((prazo) => (
                  <TableRow
                    key={prazo.id}
                    className="cursor-pointer"
                    onClick={() =>
                      router.visit(`/dashboard/diretor/prazos/${prazo.id}`)
                    }
                  >
                    <TableCell className="px-4 font-medium">
                      {prazo.titulo}
                    </TableCell>
                    <TableCell className="px-4">
                      {prazo.disciplina?.nome || 'Todas'}
                    </TableCell>
                    <TableCell className="px-4">
                      {prazo.classe?.nome || 'Todas'}
                    </TableCell>
                    <TableCell className="px-4 text-center whitespace-nowrap font-mono text-xs tabular-nums">
                      {prazo.data_limite}
                    </TableCell>
                    <TableCell className="px-4 text-center">
                      {prazo.status_label}
                    </TableCell>
                    <TableCell className="px-4 text-center">
                      {prazo.total_submissoes || 0}
                    </TableCell>
                    <TableCell className="px-4 text-right">
                      <DropdownMenu>
                        <DropdownMenuTrigger
                          asChild
                          onClick={(e) => e.stopPropagation()}
                        >
                          <Button
                            variant="ghost"
                            size="icon"
                            className="size-8 hover:cursor-pointer"
                          >
                            <MoreHorizontalIcon className="size-4" />
                            <span className="sr-only">Abrir menu</span>
                          </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end">
                          <DropdownMenuItem
                            onClick={(e) => {
                              e.stopPropagation();
                              router.visit(
                                `/dashboard/diretor/prazos/${prazo.id}`,
                              );
                            }}
                            className="hover:cursor-pointer"
                          >
                            Visualizar
                          </DropdownMenuItem>

                          <DropdownMenuItem
                            onClick={(e) => {
                              e.stopPropagation();
                              router.visit(
                                `/dashboard/diretor/prazos/${prazo.id}/edit`,
                              );
                            }}
                            className="hover:cursor-pointer"
                          >
                            Editar
                          </DropdownMenuItem>

                          {prazo.status === 'aberto' &&
                            prazo.status_label !== 'Expirado' && (
                              <DropdownMenuItem
                                onClick={(e) => {
                                  e.stopPropagation();
                                  onFechar(prazo.id);
                                }}
                                className="hover:cursor-pointer"
                              >
                                <XCircleIcon className="mr-2 size-4" />
                                Encerrar prazo
                              </DropdownMenuItem>
                            )}
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        )}
      </CardContent>

      {pagination && pagination.total > 0 && (
        <TablePagination
          pagination={pagination}
          onPageChange={onPageChange}
        />
      )}
    </Card>
  );
}