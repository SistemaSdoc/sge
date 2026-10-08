import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
  CardAction,
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
import {
  index,
  create,
  destroy,
  edit,
  restore,
} from '@/actions/App/Http/Controllers/Central/DisciplinaController';
import { useDialog } from '@/hooks/use-dialog';
import { TableSearch } from '@/components/table-search';
import { useTableSearch } from '@/hooks/use-table-search';
import { EmptyState } from '@/components/empty-state';
import { BookOpenCheck } from 'lucide-react';

export default function Index({ disciplinas, filters }) {
  const { deleteConfirm } = useDialog();
  const { search, onChange, submit, applied } = useTableSearch(
    filters?.search,
    {
      only: ['disciplinas', 'filters'],
    },
  );

  const handleDelete = (disciplina) => {
    deleteConfirm({
      title: 'Tens a certeza?',
      description: 'A disciplina será arquivada do catálogo central.',
      confirmLabel: 'Arquivar',
      confirmFn: () => router.delete(destroy(disciplina.id).url),
    });
  };

  const handleRestore = (disciplina) => {
    router.post(restore(disciplina.id).url);
  };

  const handlePageChange = (page) => {
    router.visit(index().url, {
      data: {
        ...Object.fromEntries(new URLSearchParams(window.location.search)),
        page,
      },
      preserveScroll: true,
    });
  };

  return (
    <>
      <Head title="Disciplinas" />
      <div className="mx-auto w-full max-w-7xl p-6">
        <Card className="gap-0 pb-0">
          <CardHeader className="border-b">
            <CardTitle>Disciplinas</CardTitle>
            <CardDescription>
              Disciplinas disponíveis para associação às instituições.
            </CardDescription>

            <CardAction>
              <Button asChild>
                <Link href={create().url}>Adicionar disciplina</Link>
              </Button>
            </CardAction>
          </CardHeader>

          <CardContent className="p-0!">
            <TableSearch value={search} onChange={onChange} onSubmit={submit} />
            {disciplinas.data.length === 0 ? (
              <EmptyState
                variant="table"
                icon={BookOpenCheck}
                title={
                  applied
                    ? 'Nenhuma disciplina encontrada'
                    : 'Nenhuma disciplina cadastrada'
                }
                description={
                  applied
                    ? 'Tenta ajustar a pesquisa.'
                    : 'Adicione uma disciplina ao catálogo central.'
                }
              />
            ) : (
              <Table>
                <TableHeader>
                  <TableRow className="bg-muted/72">
                    <TableHead className="px-4">Nome</TableHead>
                    <TableHead>Sigla</TableHead>
                    <TableHead>Componente</TableHead>
                    <TableHead>Carga horária</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead className="px-4 text-right">Acções</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {disciplinas.data.map((disciplina) => (
                    <TableRow key={disciplina.id}>
                      <TableCell className="px-4 font-medium">
                        {disciplina.nome}
                      </TableCell>
                      <TableCell>{disciplina.sigla || '—'}</TableCell>
                      <TableCell>{disciplina.componente || '—'}</TableCell>
                      <TableCell>{disciplina.carga_horaria} horas</TableCell>
                      <TableCell>
                        {disciplina.deleted_at
                          ? 'Arquivada'
                          : disciplina.status === 1
                            ? 'Activa'
                            : 'Inactiva'}
                      </TableCell>
                      <TableCell className="px-4 text-right">
                        <div className="flex justify-end gap-2">
                          {!disciplina.deleted_at && (
                            <>
                              <Button asChild variant="outline" size="xs">
                                <Link href={edit(disciplina.id).url}>
                                  Editar
                                </Link>
                              </Button>
                              <Button
                                variant="destructive"
                                size="xs"
                                onClick={() => handleDelete(disciplina)}
                              >
                                Arquivar
                              </Button>
                            </>
                          )}
                          {disciplina.deleted_at && (
                            <Button
                              variant="outline"
                              size="xs"
                              onClick={() => handleRestore(disciplina)}
                            >
                              Restaurar
                            </Button>
                          )}
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            )}
          </CardContent>

          <TablePagination
            pagination={disciplinas}
            onPageChange={handlePageChange}
          />
        </Card>
      </div>
    </>
  );
}
