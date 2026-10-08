import { router } from '@inertiajs/react';

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

import { show } from '@/actions/App/Http/Controllers/Tenant/Colegios/ColegioController';
import TablePagination from '@/components/table-pagination';
import { TableSearch } from '@/components/table-search';
import { useTableSearch } from '@/hooks/use-table-search';
import { EmptyState } from '@/components/empty-state';
import { School } from 'lucide-react';

export function ColegioTable({
  instituicao,
  colegios,
  filters,
  pagination = {},
  onPageChange,
}) {
  const { search, onChange, submit, applied } = useTableSearch(
    filters?.search,
    {
      only: ['colegios', 'filters'],
    },
  );
  const lista = Array.isArray(colegios) ? colegios : (colegios?.data ?? []);
  const isEmpty = lista.length === 0;

  return (
    <div className="mx-auto w-full max-w-7xl space-y-4 p-6">
      <Card className="gap-0">
        <CardHeader className="border-b">
          <CardTitle>Colegios</CardTitle>
          <CardDescription>
            Lista de colégios com Cursos Tutelados
          </CardDescription>
        </CardHeader>

        <CardContent className="p-0!">
          <TableSearch value={search} onChange={onChange} onSubmit={submit} />
          {isEmpty ? (
            <EmptyState
              variant="table"
              icon={School}
              title={
                applied
                  ? 'Nenhum colégio encontrado'
                  : 'Nenhum colégio disponível'
              }
              description={
                applied
                  ? 'Tenta ajustar a pesquisa.'
                  : 'Não existem colégios com cursos tutelados.'
              }
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/72">
                  <TableHead className="px-4">Nome</TableHead>

                  <TableHead className="px-4">Cursos Tutelados</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {lista.map((colegio) => (
                  <TableRow
                    key={colegio.id}
                    className="hover:cursor-pointer"
                    onClick={() =>
                      router.visit(
                        show({
                          instituicao: instituicao.id,
                          colegio: colegio.id,
                        }).url,
                      )
                    }
                  >
                    <TableCell className="px-4 font-medium">
                      {colegio.nome}
                    </TableCell>

                    <TableCell className="px-4 font-medium">
                      {colegio.total_cursos}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>

        <TablePagination pagination={pagination} onPageChange={onPageChange} />
      </Card>
    </div>
  );
}
