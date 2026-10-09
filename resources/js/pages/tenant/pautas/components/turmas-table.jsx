import { Link } from '@inertiajs/react';
import { ArrowUpRight, BookOpen, ExternalLink } from 'lucide-react';
import { pauta } from '@/actions/App/Http/Controllers/Tenant/PautaController';
import { EmptyState } from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import {
  Card,
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
import TablePagination from '@/components/table-pagination';
import { TableSearch } from '@/components/table-search';
import { useTableSearch } from '@/hooks/use-table-search';

export function TurmasTable({
  turmas = [],
  filtros,
  pagination = {},
  onPageChange,
}) {
  const { search, onChange, submit, applied } = useTableSearch(
    filtros?.search,
    {
      only: ['turmas', 'filtros'],
    },
  );

  return (
    <Card className="gap-0">
      <CardHeader className="border-b">
        <CardTitle>Pautas</CardTitle>
        <CardDescription>
          Lista de pautas deste curso. Clique em uma para visualizar
        </CardDescription>
      </CardHeader>

      <CardContent className="p-0!">
        <TableSearch value={search} onChange={onChange} onSubmit={submit} />
        {turmas.length === 0 ? (
          <EmptyState
            variant="table"
            icon={BookOpen}
            title={
              applied ? 'Nenhuma pauta encontrada' : 'Nenhuma pauta disponível'
            }
            description={
              applied
                ? 'Tenta ajustar a pesquisa.'
                : 'Não existem turmas para os filtros seleccionados'
            }
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Turma</TableHead>
                <TableHead className="px-4">Classe</TableHead>
                <TableHead className="px-4">Turno</TableHead>
                <TableHead className="px-4">Curso</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {turmas.map((turma) => (
                <TableRow key={turma.id}>
                  <TableCell className="px-4 font-medium">
                    {turma.nome}
                  </TableCell>
                  <TableCell className="px-4">{turma.classe ?? '—'}</TableCell>
                  <TableCell className="px-4">{turma.turno ?? '—'}</TableCell>
                  <TableCell className="px-4">{turma.curso ?? '—'}</TableCell>
                  <TableCell className="px-4 text-right">
                    {turma.can?.view_pauta && (
                      <Button asChild size="sm" variant="outline">
                        <Link href={pauta({ turma: turma.id })}>
                          Visualizar <ArrowUpRight />
                        </Link>
                      </Button>
                    )}
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
