import { Calendar1, Download, Eye, FileUp, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
  CardAction,
} from '@/components/ui/card';
import { EmptyState } from '@/components/empty-state';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import TablePagination from '@/components/table-pagination';
import {
  download,
  view,
} from '@/actions/App/Http/Controllers/Central/CalendarioAnualController';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { MoreHorizontalIcon } from 'lucide-react';

export default function CalendariosTable({
  calendarios = [],
  onAdd,
  onToggle,
  onReplace,
  onDelete,
  pagination,
  onPageChange,
}) {
  const isEmpty = calendarios.length === 0;

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <Card className="gap-0 pb-0">
        <CardHeader className="border-b">
          <CardTitle>Calendários Publicados</CardTitle>
          <CardDescription>
            Documentos anuais disponíveis para as instituições.
          </CardDescription>

          <CardAction>
            <Button type="button" onClick={onAdd}>
              Adicionar
            </Button>
          </CardAction>

        </CardHeader>
        <CardContent className="p-0!">
          {isEmpty ? (
            <EmptyState
              variant="table"
              icon={Calendar1}
              title="Nenhum calendário publicado"
              description="Adicione um calendário anual para o disponibilizar as instituições."
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/72">
                  <TableHead className="px-4">Ano</TableHead>
                  <TableHead>Ficheiro</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="px-4 text-right">Acções</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {calendarios.map((calendario) => (
                  <TableRow key={calendario.id}>
                    <TableCell className="px-4 font-medium">
                      {calendario.ano}
                    </TableCell>
                    <TableCell>{calendario.ficheiro_nome}</TableCell>
                    <TableCell>
                      {calendario.ativo ? 'Activo' : 'Inactivo'}
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
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          <DropdownMenuItem asChild>
                            <a
                              href={view(calendario.id).url}
                              target="_blank"
                              rel="noreferrer"
                            >
                              Visualizar
                            </a>
                          </DropdownMenuItem>
                          <DropdownMenuItem
                            onClick={() => onToggle(calendario)}
                          >
                            {calendario.ativo ? 'Desactivar' : 'Activar'}
                          </DropdownMenuItem>
                          <DropdownMenuItem asChild>
                            <a href={download(calendario.id).url}>Baixar</a>
                          </DropdownMenuItem>
                          <DropdownMenuItem
                            onClick={() => onReplace(calendario)}
                          >
                            Substituir
                          </DropdownMenuItem>
                          <DropdownMenuItem
                            variant="destructive"
                            onClick={() => onDelete(calendario)}
                          >
                            Eliminar
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
  );
}
