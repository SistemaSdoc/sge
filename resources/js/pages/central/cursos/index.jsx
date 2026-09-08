import { Head, Link, router } from '@inertiajs/react';
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
import {
  create,
  destroy,
  edit,
  restore,
} from '@/actions/App/Http/Controllers/Central/CursoController';
import { useDialog } from '@/hooks/use-dialog';

export default function Index({ cursos }) {
  const { deleteConfirm } = useDialog();

  const handleDelete = (curso) => {
    deleteConfirm({
      title: 'Tens a certeza?',
      description: 'O curso será removido do catálogo central.',
      confirmLabel: 'Remover',
      confirmFn: () => router.delete(destroy(curso.id).url),
    });
  };

  const handleRestore = (curso) => {
    router.post(restore(curso.id).url);
  };

  return (
    <>
      <Head title="Catálogo de cursos" />
      <div className="mx-auto w-full max-w-6xl space-y-4 p-6">
        <Card className='gap-0'>
          <CardHeader className="border-b">
            <div className="flex items-start justify-between gap-4">
              <div>
                <CardTitle>Catálogo de cursos</CardTitle>
                <CardDescription>
                  Cursos disponíveis para associação às instituições.
                </CardDescription>
              </div>
              <Button asChild>
                <Link href={create().url}>Adicionar curso</Link>
              </Button>
            </div>
          </CardHeader>
          <CardContent className="p-0!">
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/72">
                  <TableHead className="px-4">Nome</TableHead>
                  <TableHead>Duração</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="px-4 text-right">Acções</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {cursos.data.map((curso) => (
                  <TableRow key={curso.id}>
                    <TableCell className="px-4 font-medium">{curso.nome}</TableCell>
                    <TableCell>{curso.duracao_anos} anos</TableCell>
                    <TableCell>
                      {curso.deleted_at
                        ? 'Arquivado'
                        : curso.status === 1
                          ? 'Activo'
                          : 'Inactivo'}
                    </TableCell>
                    <TableCell className="px-4 text-right">
                      <div className="flex justify-end gap-2">
                        {!curso.deleted_at && (
                          <>
                            <Button asChild variant="outline" size="xs">
                              <Link href={edit(curso.id).url}>Editar</Link>
                            </Button>
                            <Button
                              variant="destructive"
                              size="xs"
                              onClick={() => handleDelete(curso)}
                            >
                              Arquivar
                            </Button>
                          </>
                        )}
                        {curso.deleted_at && (
                          <Button
                            variant="outline"
                            size="xs"
                            onClick={() => handleRestore(curso)}
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
          </CardContent>
          <TablePagination
            pagination={{
              current_page: cursos.current_page,
              last_page: cursos.last_page,
            }}
            onPageChange={(page) =>
              router.get(cursos.path, { page }, { preserveScroll: true })
            }
          />
        </Card>
      </div>
    </>
  );
}
