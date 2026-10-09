import { Link, router } from '@inertiajs/react';
import { Minus, BookIcon } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';

import { EmptyState } from '@/components/empty-state';
import {
  create,
  show,
  edit,
} from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoController';
import TablePagination from '@/components/table-pagination';
import { TableSearch } from '@/components/table-search';
import { useTableSearch } from '@/hooks/use-table-search';
import { TutelaStatusBadge } from './tutela-status-badge';

export function CursosTuteladosTable({
  data = [],
  filters,
  instituicaoId,
  deleteFn,
  pagination = {},
  onPageChange,
  can = {},
}) {
  const { search, onChange, submit, applied } = useTableSearch(
    filters?.search,
    {
      only: ['cursos', 'filters'],
    },
  );

  // "vazio total" só se não há dados E não há pesquisa aplicada
  const isEmpty = data.length === 0 && !applied;

  const canCreate = Boolean(can?.create_curso || can?.create);

  return (
    <Card className="gap-0 pb-0">
      {/* Header */}
      <CardHeader className="border-b">
        <div className="flex w-full! flex-col items-start justify-between gap-2 md:flex-row">
          <div>
            <CardTitle>Cursos</CardTitle>
            <CardDescription>
              Cursos lecionados por esta instituição
            </CardDescription>
          </div>

          {canCreate && (
            <CardAction className="w-full sm:w-auto">
              <Button asChild className="w-full sm:w-auto">
                <Link href={create(instituicaoId).url}>Adicionar Curso</Link>
              </Button>
            </CardAction>
          )}
        </div>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={BookIcon}
            title="Nenhum curso cadastrado"
            description="Comece adicionando o primeiro curso à instituição"
            action={
              canCreate
                ? {
                    label: 'Adicionar Curso',
                    href: create(instituicaoId).url,
                    variant: 'outline',
                  }
                : undefined
            }
          />
        ) : (
          <>
            <TableSearch value={search} onChange={onChange} onSubmit={submit} />

            {/* Tabela */}
            {data.length === 0 ? (
              <EmptyState
                variant="table"
                icon={BookIcon}
                title="Nenhum curso encontrado"
                description="Tenta ajustar a pesquisa"
              />
            ) : (
              <Table>
                <TableHeader>
                  <TableRow className="bg-muted/72">
                    <TableHead className="px-4">Nome</TableHead>
                    <TableHead className="text-center">Tutelado por</TableHead>
                    <TableHead className="text-center">Status</TableHead>
                    <TableHead className="px-4 text-right">Acções</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {data.map((curso) => (
                    <TableRow
                      key={curso.id}
                      className={
                        curso.can?.view ? 'hover:cursor-pointer' : 'opacity-70'
                      }
                      aria-disabled={!curso.can?.view}
                      onClick={() => {
                        if (curso.can?.view) {
                          router.visit(
                            show({
                              instituicao: instituicaoId,
                              cursoTutelado: curso?.id,
                            }).url,
                          );
                        }
                      }}
                    >
                      <TableCell className="px-4 font-medium">
                        {curso.nome}
                      </TableCell>

                      <TableCell className="text-center">
                        <div className="flex flex-col items-center gap-1">
                          <span>
                            {curso.instituicao_tutora || (
                              <Minus
                                size={15}
                                className="text-muted-foreground"
                              />
                            )}
                          </span>
                          {curso.instituicao_tutora_pendente && (
                            <span className="rounded border border-muted-foreground/25 px-2 py-0.5 text-xs text-muted-foreground">
                              Troca pendente:{' '}
                              {curso.instituicao_tutora_pendente}
                            </span>
                          )}
                        </div>
                      </TableCell>

                      <TableCell className="text-center">
                        {curso.status ? (
                          <TutelaStatusBadge status={curso.status} />
                        ) : (
                          '__'
                        )}
                      </TableCell>

                      <TableCell className="px-4 text-right">
                        <div className="flex items-center justify-end gap-2">
                          {/* Botão Editar */}
                          {curso.can?.update && (
                            <Button
                              variant="outline"
                              size="xs"
                              className="hover:cursor-pointer"
                              onClick={(e) => {
                                e.stopPropagation();
                                router.visit(
                                  edit({
                                    instituicao: instituicaoId,
                                    cursoTutelado: curso.id,
                                  }).url,
                                );
                              }}
                            >
                              Editar
                            </Button>
                          )}

                          {/* Botão Remover */}
                          {curso.can?.delete && (
                            <Button
                              variant="destructive"
                              size="xs"
                              className="hover:cursor-pointer"
                              onClick={(e) => {
                                e.stopPropagation();
                                deleteFn(curso.id);
                              }}
                            >
                              Remover
                            </Button>
                          )}
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            )}
          </>
        )}
      </CardContent>

      <TablePagination pagination={pagination} onPageChange={onPageChange} />
    </Card>
  );
}
