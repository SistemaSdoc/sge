import {
  Calendar1,
  Download,
  Eye,
  FileText,
  FileUp,
  Plus,
  Trash2,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { EmptyState } from '@/components/empty-state';
import { Switch } from '@/components/ui/switch';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  download,
  view,
} from '@/actions/App/Http/Controllers/Tenant/CalendarioAnualController';
import TablePagination from '@/components/table-pagination';

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
    <Card className="mx-auto w-full max-w-7xl gap-0 pb-0">
      <CardHeader className="border-b">
        <CardTitle>Calendários publicados</CardTitle>
        <CardDescription>
          Documentos anuais disponíveis para as instituições.
        </CardDescription>
      </CardHeader>
      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={Calendar1}
            title="Nenhum calendário publicado"
            description="Ainda não foi disponibilizado um calendário anual."
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Ano</TableHead>
                <TableHead>Ficheiro</TableHead>
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
                  <TableCell className="px-4 text-right">
                    <div className="flex justify-end gap-2">
                      <Button asChild variant="outline" size="xs">
                        <a
                          href={view(calendario.id).url}
                          target="_blank"
                          rel="noreferrer"
                        >
                          <Eye />
                          Visualizar
                        </a>
                      </Button>
                      <Button asChild variant="outline" size="xs">
                        <a href={download(calendario.id).url}>
                          <Download />
                          Baixar
                        </a>
                      </Button>
                    </div>
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
