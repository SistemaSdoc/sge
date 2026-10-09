import { Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { ChevronDownIcon, MoreHorizontalIcon, UsersIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
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
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import TablePagination from '@/components/table-pagination';
import { TableSearch } from '@/components/table-search';
import { useTableSearch } from '@/hooks/use-table-search';
import { edit } from '@/actions/App/Http/Controllers/Tenant/AlunoController';
import { create } from '@/actions/App/Http/Controllers/Tenant/InscricaoController';

export function AlunoTable({
  data,
  filters,
  deleteFn,
  pagination = {},
  onPageChange,
  can,
  anoLectivoActual,
  anosLectivos = [],
  onAnoLectivoChange,
  atribuirTurmaFn,
}) {
  const { search, onChange, submit, applied } = useTableSearch(
    filters?.search,
    {
      only: ['alunos', 'filters'],
    },
  );
  const isEmpty = !data || data.length === 0;
  const hasActionColumn = data?.some((aluno) => aluno.can?.update);
  const createHref = create.url({
    query: { ano_lectivo_id: anoLectivoActual },
  });

  return (
    <div className="mx-auto w-full max-w-7xl p-4 md:p-6">
      <Card className="gap-0">
        <CardHeader className="flex flex-col gap-3 border-b sm:flex-row sm:items-center sm:justify-between">
          <div>
            <CardTitle>Alunos</CardTitle>
            <CardDescription>Lista de alunos cadastrados</CardDescription>
          </div>
          {can?.create && (
            <div className="w-full sm:w-auto">
              <Button asChild className="w-full sm:w-auto">
                <Link href={createHref}>Adicionar Aluno</Link>
              </Button>
            </div>
          )}
        </CardHeader>

        <CardContent className="p-0!">
          <div className="flex justify-end border-b bg-muted/30 px-4 py-3">
            <div className="flex w-full max-w-sm flex-col gap-2 md:w-auto md:max-w-none md:flex-row md:gap-2">
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button
                    variant="outline"
                    aria-label="Filtrar por ano lectivo"
                    className="w-full hover:cursor-pointer md:w-auto"
                  >
                    {anoLectivoActual
                      ? anosLectivos.find((ano) => ano.id === anoLectivoActual)
                          ?.nome
                      : 'Filtrar'}
                    <ChevronDownIcon aria-hidden="true" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start" className="md:w-48">
                  <DropdownMenuLabel>Anos Lectivos</DropdownMenuLabel>
                  <DropdownMenuSeparator />
                  {anosLectivos.map((ano) => (
                    <DropdownMenuItem
                      key={ano.id}
                      onClick={() => onAnoLectivoChange(ano.id)}
                      className="hover:cursor-pointer"
                    >
                      {ano.nome}
                    </DropdownMenuItem>
                  ))}
                </DropdownMenuContent>
              </DropdownMenu>

              <TableSearch
                bare
                buttonGroupClassName="max-w-none md:max-w-xs"
                value={search}
                onChange={onChange}
                onSubmit={submit}
              />
            </div>
          </div>
          {isEmpty ? (
            <EmptyState
              variant="table"
              icon={UsersIcon}
              title={
                applied ? 'Nenhum aluno encontrado' : 'Nenhum aluno cadastrado'
              }
              description={
                applied
                  ? 'Tenta ajustar a pesquisa.'
                  : 'Comece adicionando o primeiro aluno à tabela'
              }
              action={
                can?.create
                  ? {
                      label: 'Adicionar Aluno',
                      href: createHref,
                      variant: 'outline',
                    }
                  : undefined
              }
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/72">
                  <TableHead className="px-4">Nome</TableHead>
                  <TableHead className="px-4">Curso</TableHead>
                  <TableHead className="px-4">Turno</TableHead>
                  <TableHead className="px-4">Turma</TableHead>
                  <TableHead className="px-4">Classe</TableHead>
                  {/* <TableHead className="px-4">Propina</TableHead> */}
                  {hasActionColumn && (
                    <TableHead className="px-4 text-right">Acções</TableHead>
                  )}
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.map((aluno) => (
                  <TableRow
                    key={aluno.id}
                    className={
                      aluno.can?.view ? 'hover:cursor-pointer' : 'opacity-70'
                    }
                    aria-disabled={!aluno.can?.view}
                    onClick={() => {
                      if (aluno.can?.view) {
                        router.visit(`/dashboard/alunos/${aluno.id}`);
                      }
                    }}
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
                      {aluno.classe}
                    </TableCell>
                    {/* <TableCell className="px-4">
                        {aluno.propina_status === 'pagou' && (
                          <span className="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                            Pagou
                          </span>
                        )}
                        {aluno.propina_status === 'atrasado' && (
                          <span className="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                            Em atraso
                          </span>
                        )}
                        {aluno.propina_status === 'sem_turma' && (
                          <span className="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                            Sem turma
                          </span>
                        )}
                      </TableCell> */}
                    {hasActionColumn && (
                      <TableCell className="px-4 text-right">
                        {aluno.can?.update && (
                          <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                              <Button
                                variant="ghost"
                                size="icon"
                                className="size-8"
                                onClick={(e) => e.stopPropagation()}
                                onPointerDown={(e) => e.stopPropagation()}
                              >
                                <MoreHorizontalIcon />
                                <span className="sr-only">Open menu</span>
                              </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                              <DropdownMenuItem
                                onClick={(e) => {
                                  e.stopPropagation();
                                  router.visit(edit({ id: aluno.id }));
                                }}
                              >
                                Editar
                              </DropdownMenuItem>
                              {/*

                                <DropdownMenuItem
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    atribuirTurmaFn(aluno, e);
                                  }}
                                >
                                  Atribuir Turma
                                </DropdownMenuItem>
                                */}
                              <DropdownMenuSeparator />
                            </DropdownMenuContent>
                          </DropdownMenu>
                        )}
                      </TableCell>
                    )}
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
