import { Button } from '@/components/ui/button';
import { ChevronDownIcon, Search, UsersIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
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

import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
  DropdownMenuLabel,
  DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';

import { Input } from '@/components/ui/input';
import { show } from '@/actions/App/Http/Controllers/ClasseTurnoTurmaController';
import TablePagination from '@/components/table-pagination';

export function TurmaTable({
  turmas,
  can = {},
  deleteFn,
  pagination = {},
  onPageChange,
  anosLectivos = [],
  anoLectivoActual,
  onAnoLectivoChange,
  handleAdicionarTurma,
}) {
  const lista = Array.isArray(turmas) ? turmas : (turmas?.data ?? []);
  const isEmpty = lista.length === 0;
  const hasActionColumn = lista.some((turma) => turma.can?.view);

  return (
    <Card className="gap-0">
      <CardHeader className="border-b">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <CardTitle>Turmas</CardTitle>
            <CardDescription>
              Lista de turmas cadastradas no ano lectivo{' '}
              <span className="font-semibold">
                {anosLectivos.find((ano) => ano.id === anoLectivoActual).nome}
              </span>
            </CardDescription>
          </div>
          <CardAction className="w-full sm:w-auto">
            {can?.create && (
              <Button
                className="w-full sm:w-auto"
                onClick={handleAdicionarTurma}
              >
                Adicionar Turma
              </Button>
            )}
          </CardAction>
        </div>
      </CardHeader>

      <CardContent className="p-0!">
        <div className="flex justify-end border-b bg-muted/30 px-4 py-3">
          <div className="flex max-w-sm gap-0.5">
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="outline" aria-label="Filter">
                  {anoLectivoActual
                    ? anosLectivos.find((a) => a.id === anoLectivoActual)?.nome
                    : 'Filtrar'}
                  <ChevronDownIcon aria-hidden="true" />
                </Button>
              </DropdownMenuTrigger>

              <DropdownMenuContent align="start" className="w-48">
                <DropdownMenuLabel>Anos Lectivos</DropdownMenuLabel>
                <DropdownMenuSeparator />
                {anosLectivos.map((ano) => (
                  <DropdownMenuItem
                    key={ano.id}
                    onClick={() => onAnoLectivoChange(ano.id)}
                    className={anoLectivoActual === ano.id ? 'bg-muted' : ''}
                  >
                    {ano.nome}
                  </DropdownMenuItem>
                ))}
              </DropdownMenuContent>
            </DropdownMenu>
            <Input placeholder="Pesquisar..." className="" />
            <Button variant="outline" size="icon">
              <Search />
              <span className="sr-only">Pesquisar</span>
            </Button>
          </div>
        </div>

        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={UsersIcon}
            title="Nenhuma turma adicionada, ainda"
            description="Ainda não cadrastou nenhum turma neste ano lectivo."
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead className="px-4">Curso</TableHead>
                <TableHead className="px-4">Classe</TableHead>
                <TableHead className="px-4">Turno</TableHead>
                {hasActionColumn && (
                  <TableHead className="px-4 text-right">Acções</TableHead>
                )}
              </TableRow>
            </TableHeader>
            <TableBody>
              {lista.map((turma) => (
                <TableRow
                  key={turma.id}
                  className={
                    turma.can?.view ? 'hover:cursor-pointer' : 'opacity-70'
                  }
                  onClick={() => {
                    if (turma.can?.view) {
                      router.visit(
                        show({
                          instituicao: turma.instituicao.id,
                          cursoTutelado: turma.curso.id,
                          cursoClasse: turma.classe.id,
                          cursoClasseTurno: turma.turno.id,
                          turma: turma.id,
                        }),
                      );
                    }
                  }}
                >
                  <TableCell className="px-4 font-medium">
                    {turma.nome}
                  </TableCell>

                  <TableCell className="px-4 font-medium">
                    {turma?.curso?.nome}
                  </TableCell>

                  <TableCell className="px-4 font-medium">
                    {turma?.classe?.nome}
                  </TableCell>

                  <TableCell className="px-4 font-medium">
                    {turma?.turno?.nome}
                  </TableCell>
                  {hasActionColumn && (
                    <TableCell className="px-4 text-right">
                      {turma.can?.view && (
                        <Button
                          variant="outline"
                          size="xs"
                          onClick={(e) => {
                            e.stopPropagation();
                            router.visit(
                              show({
                                instituicao: turma.instituicao.id,
                                cursoTutelado: turma.curso.id,
                                cursoClasse: turma.classe.id,
                                cursoClasseTurno: turma.turno.id,
                                turma: turma.id,
                              }),
                            );
                          }}
                        >
                          Ver detalher
                        </Button>
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
  );
}
