import { router } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/empty-state';
import { formatStatusInscricao } from '@/utils/format-status';
import {
  ChevronDownIcon,
  MoreHorizontalIcon,
  UserCheckIcon,
} from 'lucide-react';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  create,
  show,
} from '@/actions/App/Http/Controllers/Tenant/InscricaoController';
import TablePagination from '@/components/table-pagination';
import { TableSearch } from '@/components/table-search';
import { useTableSearch } from '@/hooks/use-table-search';
import { destroy } from '@/actions/App/Http/Controllers/Tenant/InscricaoController';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

export function InscricaoTable({
  inscricoes,
  anoLectivoActual,
  anosLectivos = [],
  onAnoLectivoChange,
  can = {},
  updateFn,
  pagination = {},
  onPageChange,
  entityLabel = 'Matrícula',
  entityLabelPlural = 'Matrículas',
  temNotaTeste = false,
  destroyFn,
  reativarFn,
  filters,
  cursosParaMatricula = [],
}) {
  const { search, onChange, submit, applied } = useTableSearch(
    filters?.search,
    {
      only: ['inscricoes', 'filters'],
    },
  );
  const [nota, setNota] = useState('');
  const [inscricaoSelecionada, setInscricaoSelecionada] = useState(null);
  const [cursoParaMatricula, setCursoParaMatricula] = useState(
    cursosParaMatricula[0]?.id ?? '',
  );
  const isCourseSecretary = cursosParaMatricula.length > 0;
  const createHref = isCourseSecretary
    ? cursoParaMatricula
      ? create.url({
          query: {
            curso_tutelado_id: cursoParaMatricula,
            ano_lectivo_id: anoLectivoActual,
          },
        })
      : undefined
    : create.url({ query: { ano_lectivo_id: anoLectivoActual } });
  const isEmpty = !inscricoes || inscricoes.length === 0;
  const hasActionColumn =
    temNotaTeste &&
    inscricoes?.some((inscricao) => inscricao.status && inscricao.can?.update);

  return (
    <>
      <Dialog
        open={!!inscricaoSelecionada}
        onOpenChange={() => {
          setInscricaoSelecionada(null);
          setNota('');
        }}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Definir nota da prova</DialogTitle>
          </DialogHeader>

          <div className="flex flex-col gap-2">
            <Label htmlFor="nota">Nota (0 - 20)</Label>
            <Input
              id="nota"
              type="number"
              min={0}
              max={20}
              value={nota}
              onChange={(e) => setNota(e.target.value)}
              placeholder="Ex: 14"
            />
          </div>

          <DialogFooter>
            <Button
              onClick={() => {
                updateFn(inscricaoSelecionada, Number(nota));
                setInscricaoSelecionada(null);
                setNota('');
              }}
            >
              Guardar
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Card className="gap-0">
        <CardHeader className="flex flex-col gap-3 border-b sm:flex-row sm:items-center sm:justify-between">
          <div>
            <CardTitle>{entityLabelPlural}</CardTitle>
            <CardDescription>
              Lista de {entityLabelPlural.toLowerCase()}
            </CardDescription>
          </div>
          {can.create && (
            <div className="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
              {isCourseSecretary && cursosParaMatricula.length > 1 ? (
                <>
                  <Select
                    value={cursoParaMatricula}
                    onValueChange={setCursoParaMatricula}
                  >
                    <SelectTrigger className="w-full sm:w-52">
                      <SelectValue placeholder="Escolha o curso" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectLabel>Os meus cursos</SelectLabel>
                        {cursosParaMatricula.map((curso) => (
                          <SelectItem key={curso.id} value={String(curso.id)}>
                            {curso.nome}
                          </SelectItem>
                        ))}
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  <Button
                    asChild
                    disabled={!createHref}
                    className="w-full sm:w-auto"
                  >
                    <Link href={createHref ?? '#'}>Matricular</Link>
                  </Button>
                </>
              ) : (
                <Button asChild className="w-full sm:w-auto">
                  <Link href={createHref ?? '#'}>
                    {isCourseSecretary ? 'Matricular' : 'Adicionar'}
                  </Link>
                </Button>
              )}
            </div>
          )}
        </CardHeader>

        <CardContent className="p-0!">
          <div className="flex flex-col gap-2 border-b bg-muted/30 px-4 py-3 sm:flex-row sm:items-center sm:justify-end">
            <div className="flex w-full max-w-sm flex-col gap-2 sm:w-auto sm:flex-row">
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button
                    variant="outline"
                    aria-label="Filtrar por ano lectivo"
                    className="w-full hover:cursor-pointer sm:w-auto"
                  >
                    {anoLectivoActual
                      ? anosLectivos.find((ano) => ano.id === anoLectivoActual)
                          ?.nome
                      : 'Filtrar'}
                    <ChevronDownIcon aria-hidden="true" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start" className="sm:w-48">
                  <DropdownMenuLabel>Anos Lectivos</DropdownMenuLabel>
                  <DropdownMenuSeparator />
                  {anosLectivos.map((ano) => (
                    <DropdownMenuItem
                      key={ano.id}
                      onClick={() => onAnoLectivoChange(ano.id)}
                      className={`${anoLectivoActual === ano.id ? 'bg-muted' : ''} hover:cursor-pointer`}
                    >
                      {ano.nome}
                    </DropdownMenuItem>
                  ))}
                </DropdownMenuContent>
              </DropdownMenu>

              <TableSearch
                bare
                buttonGroupClassName="max-w-none sm:max-w-xs"
                value={search}
                onChange={onChange}
                onSubmit={submit}
              />
            </div>
          </div>
          {isEmpty ? (
            <EmptyState
              variant="table"
              icon={UserCheckIcon}
              title={
                applied
                  ? `Nenhuma ${entityLabel.toLowerCase()} encontrada`
                  : `Nenhuma ${entityLabel.toLowerCase()} cadastrada`
              }
              description={
                applied
                  ? 'Tenta ajustar a pesquisa.'
                  : `Comece adicionando a primeira ${entityLabel.toLowerCase()} à tabela`
              }
              action={
                can.create && createHref
                  ? {
                      label: `Adicionar ${entityLabel}`,
                      href: createHref ?? '#',
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
                  <TableHead className="px-4">Status</TableHead>
                  {hasActionColumn && (
                    <TableHead className="px-4 text-right">Acções</TableHead>
                  )}
                </TableRow>
              </TableHeader>
              <TableBody>
                {inscricoes.map((inscricao) => (
                  <TableRow
                    key={inscricao.id}
                    className={
                      inscricao.can?.view
                        ? 'hover:cursor-pointer'
                        : 'opacity-70'
                    }

                    onClick={() => {
                      if (inscricao.can?.view) {
                        router.visit(show(inscricao.id).url);
                      }
                    }}
                  >
                    <TableCell className="px-4 font-medium">
                      {inscricao.candidato}
                    </TableCell>
                    <TableCell className="px-4 font-medium">
                      {inscricao.curso}
                    </TableCell>
                    <TableCell className="px-4 font-medium">
                      {inscricao.turno}
                    </TableCell>
                    <TableCell className="px-4 font-medium">
                      {formatStatusInscricao(inscricao.status)}
                    </TableCell>
                    {hasActionColumn && (
                      <TableCell className="px-4 text-right">
                        {inscricao.can?.cancelar &&
                          inscricao.status === 'aprovado' && (
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
                                <DropdownMenuItem
                                  variant="destructive"
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    destroyFn(inscricao.id);
                                  }}
                                >
                                  Cancelar Matrícula
                                </DropdownMenuItem>
                              </DropdownMenuContent>
                            </DropdownMenu>
                          )}

                        {inscricao.can?.reativar &&
                          inscricao.status === 'cancelado' && (
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
                                <DropdownMenuItem
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    reativarFn(inscricao.id);
                                  }}
                                >
                                  Reativar Matrícula
                                </DropdownMenuItem>
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
    </>
  );
}
