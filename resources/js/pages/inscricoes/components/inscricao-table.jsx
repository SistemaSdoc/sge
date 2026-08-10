import { router } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/empty-state';
import { formatStatusInscricao } from '@/utils/format-status';
import { MoreHorizontalIcon, UserCheckIcon } from 'lucide-react';
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
  CardAction,
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
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { create, show } from '@/routes/inscricoes';
import TablePagination from '@/components/table-pagination';
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
}) {
  const [nota, setNota] = useState('');
  const [inscricaoSelecionada, setInscricaoSelecionada] = useState(null);
  const isEmpty = !inscricoes || inscricoes.length === 0;
  const hasActionColumn =
    temNotaTeste &&
    inscricoes?.some(
      (inscricao) => inscricao.status === 'pendente' && inscricao.can?.update,
    );

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
        <CardHeader className="border-b">
          <CardTitle>{entityLabelPlural}</CardTitle>
          <CardDescription>Lista de {entityLabelPlural.toLowerCase()}</CardDescription>
          {can.create && (
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

              <Button asChild>
                <Link href={create.url()}>Adicionar</Link>
              </Button>
            </CardAction>
          )}
        </CardHeader>

        <CardContent className="p-0!">
          {isEmpty ? (
            <EmptyState
              variant="table"
              icon={UserCheckIcon}
              title={`Nenhuma ${entityLabel.toLowerCase()} cadastrada`}
              description={`Comece adicionando a primeira ${entityLabel.toLowerCase()} à tabela`}
              action={
                can.create
                  ? {
                      label: `Adicionar ${entityLabel}`,
                      href: create.url(),
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
                        {inscricao.status === 'pendente' &&
                          inscricao.can?.update && (
                            <DropdownMenu>
                              <DropdownMenuTrigger asChild>
                                <Button
                                  variant="ghost"
                                  size="icon"
                                  className="size-8"
                                >
                                  <MoreHorizontalIcon />
                                  <span className="sr-only">Open menu</span>
                                </Button>
                              </DropdownMenuTrigger>
                              <DropdownMenuContent
                                align="end"
                                className="w-auto!"
                              >
                                <DropdownMenuItem
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    setInscricaoSelecionada(inscricao.id);
                                  }}
                                >
                                  Definir nota da prova
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
