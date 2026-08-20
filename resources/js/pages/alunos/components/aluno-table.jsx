import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Filter, MoreHorizontalIcon, UsersIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
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
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import TablePagination from '@/components/table-pagination';
import { edit } from '@/actions/App/Http/Controllers/Tenant/AlunoController';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

export function AlunoTable({
  data,
  deleteFn,
  pagination = {},
  onPageChange,
  can,
  anoLectivoActual,
  anosLectivos = [],
  onAnoLectivoChange,
  atribuirTurmaFn,
}) {
  const isEmpty = !data || data.length === 0;
  const hasActionColumn = data?.some((aluno) => aluno.can?.update);
  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <Card className="gap-0">
        <CardHeader className="border-b">
          <CardTitle>Alunos</CardTitle>
          <CardDescription>Lista de alunos cadastrados</CardDescription>
          <CardAction className="flex gap-3">
            <Select
              value={anoLectivoActual ?? ''}
              onValueChange={onAnoLectivoChange}
            >
              <SelectTrigger id="ano-lectivo" className="w-48">
                <SelectValue placeholder="Selecione o ano lectivo" />
              </SelectTrigger>
              <SelectContent>
                <SelectGroup>
                  <SelectLabel>Anos Lectivos</SelectLabel>
                  {anosLectivos?.map((ano) => (
                    <SelectItem key={ano.id} value={ano.id}>
                      {ano.nome}
                    </SelectItem>
                  ))}
                </SelectGroup>
              </SelectContent>
            </Select>
            <Field>
              <div className="flex gap-2">
                <Input placeholder="Digite para pesquisar..." />
                <Button variant="outline">Pesquisar</Button>
              </div>
            </Field>
          </CardAction>
        </CardHeader>

        <CardContent className="p-0!">
          {isEmpty ? (
            <EmptyState
              variant="table"
              icon={UsersIcon}
              title="Nenhum aluno cadastrado"
              description="Comece adicionando o primeiro aluno à tabela"
              action={
                can?.create
                  ? {
                      label: 'Adicionar Aluno',
                      href: '/dashboard/inscricoes/create', // ← alterado de /alunos/create
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
                    className="hover:cursor-pointer"
                    onClick={() =>
                      router.visit(`/dashboard/alunos/${aluno.id}`)
                    } // alterado
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
