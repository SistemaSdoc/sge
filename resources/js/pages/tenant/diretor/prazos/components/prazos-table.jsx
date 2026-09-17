import { router } from '@inertiajs/react';
import { PlusIcon, LayersIcon, MoreHorizontalIcon, EyeIcon, EditIcon, XCircleIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
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
      <CardHeader className="border-b flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <CardTitle>Prazos de Provas</CardTitle>
          <CardDescription>
            Lista de prazos criados para submissão de provas.
          </CardDescription>
        </div>
        <CardAction className="w-full sm:w-auto">
          <Button onClick={onCreate} className="w-full sm:w-auto">
            <PlusIcon className="mr-1.5 size-4" />
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
          // Scroll horizontal em mobile
          <div className="w-full overflow-x-auto">
            <Table className="min-w-[800px]">
              <TableHeader>
                <TableRow className="bg-muted/72">
                  <TableHead className="px-4">Título</TableHead>
                  <TableHead className="px-4">Disciplina</TableHead>
                  <TableHead className="px-4">Classe</TableHead>
                  <TableHead className="px-4 text-center">Data Limite</TableHead>
                  <TableHead className="px-4 text-center">Status</TableHead>
                  <TableHead className="px-4 text-center">Submissões</TableHead>
                  <TableHead className="px-4 text-right">Ações</TableHead>
                </TableRow>
              </TableHeader>

              <TableBody>
                {prazos.map((prazo) => (
                  <TableRow
                    key={prazo.id}
                    className="cursor-pointer"
                    onClick={() => router.visit(`/dashboard/diretor/prazos/${prazo.id}`)}
                  >
                    <TableCell className="px-4 font-medium">{prazo.titulo}</TableCell>
                    <TableCell className="px-4">{prazo.disciplina?.nome || 'Todas'}</TableCell>
                    <TableCell className="px-4">{prazo.classe?.nome || 'Todas'}</TableCell>
                    <TableCell className="px-4 text-center">{prazo.data_limite}</TableCell>
                    <TableCell className="px-4 text-center">
                      <Badge variant="outline" className={prazo.badge_class}>
                        {prazo.status_label}
                      </Badge>
                    </TableCell>
                    <TableCell className="px-4 text-center">
                      <Badge variant="secondary">{prazo.total_submissoes || 0}</Badge>
                    </TableCell>
                    <TableCell className="px-4 text-right">
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild onClick={(e) => e.stopPropagation()}>
                          <Button variant="ghost" size="icon" className="size-8">
                            <MoreHorizontalIcon className="size-4" />
                            <span className="sr-only">Abrir menu</span>
                          </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end">
                          <DropdownMenuItem
                            onClick={(e) => {
                              e.stopPropagation();
                              router.visit(`/dashboard/diretor/prazos/${prazo.id}`);
                            }}
                          >
                            <EyeIcon className="mr-2 size-4" />
                            Ver detalhes
                          </DropdownMenuItem>

                          <DropdownMenuItem
                            onClick={(e) => {
                              e.stopPropagation();
                              router.visit(`/dashboard/diretor/prazos/${prazo.id}/edit`);
                            }}
                          >
                            <EditIcon className="mr-2 size-4" />
                            Editar
                          </DropdownMenuItem>

                          {prazo.status === 'aberto' && prazo.status_label !== 'Expirado' && (
                            <DropdownMenuItem
                              variant="destructive"
                              onClick={(e) => {
                                e.stopPropagation();
                                onFechar(prazo.id);
                              }}
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

      {pagination && (
        <TablePagination pagination={pagination} onPageChange={onPageChange} />
      )}
    </Card>
  );
}