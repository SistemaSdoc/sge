import { Link, router } from '@inertiajs/react';
import { MoreHorizontalIcon, LayersIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { Button } from '@/components/ui/button';

import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';

import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

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
  edit,
} from '@/actions/App/Http/Controllers/AnoLectivoController';

const ESTADO_CONFIG = {
  planeado: {
    label: 'Planeado',
    dot: 'bg-blue-500',
    text: 'text-blue-700 dark:text-blue-400',
    bg: 'bg-blue-50 dark:bg-blue-950/40',
    pulse: false,
  },
  a_decorrer: {
    label: 'A decorrer',
    dot: 'bg-emerald-500',
    text: 'text-emerald-700 dark:text-emerald-400',
    bg: 'bg-emerald-50 dark:bg-emerald-950/40',
    pulse: true,
  },
  encerrado: {
    label: 'Encerrado',
    dot: 'bg-muted-foreground/50',
    text: 'text-muted-foreground',
    bg: 'bg-muted',
    pulse: false,
  },
};

function EstadoBadge({ estado }) {
  const config = ESTADO_CONFIG[estado] ?? ESTADO_CONFIG.encerrado;

  return (
    <span
      className={`inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium ${config.bg} ${config.text}`}
    >
      <span className="relative flex size-1.5">
        {config.pulse && (
          <span
            className={`absolute inline-flex h-full w-full animate-ping ${config.dot} opacity-75`}
          />
        )}
        <span
          className={`relative inline-flex size-1.5 ${config.dot}`}
        />
      </span>
      {config.label}
    </span>
  );
}

function formatarData(data) {
  return new Intl.DateTimeFormat('pt-PT', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(new Date(data));
}

export default function AnoLectivoTable({
  anosLectivos = [],
  can,
  deleteFn,
  pagination = {},
  onPageChange,
}) {
  const hasAnyAction = anosLectivos.some(
    (anoLectivo) => anoLectivo.can.update || anoLectivo.can.delete,
  );
  const isEmpty = !anosLectivos || anosLectivos.length === 0;

  return (
    <Card className="mx-auto w-full max-w-7xl gap-0">
      <CardHeader className="border-b">
        <CardTitle>Anos Lectivos</CardTitle>
        <CardDescription>Lista de anos lectivos cadastrados</CardDescription>
        {can?.create ||
          (true && (
            <CardAction>
              <Button asChild>
                <Link href={create().url}>Adicionar</Link>
              </Button>
            </CardAction>
          ))}
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={LayersIcon}
            title="Nenhum ano lectivo cadastrado"
            description="Comece adicionando o primeiro ano lectivo à tabela"
            action={
              can?.create
                ? {
                    label: 'Adicionar ano lectivo',
                    href: create().url,
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
                <TableHead className="px-4">Data de Início</TableHead>
                <TableHead className="px-4">Data de Fim</TableHead>
                <TableHead className="px-4">Estado</TableHead>
                {hasAnyAction && (
                  <TableHead className="px-4 text-right">Acções</TableHead>
                )}
              </TableRow>
            </TableHeader>
            <TableBody>
              {anosLectivos.map((anoLectivo) => (
                <TableRow key={anoLectivo.id}>
                  <TableCell className="px-4 font-medium">
                    {anoLectivo.nome}
                  </TableCell>

                  <TableCell className="px-4 font-medium">
                    {formatarData(anoLectivo.data_inicio)}
                  </TableCell>

                  <TableCell className="px-4 font-medium">
                    {formatarData(anoLectivo.data_fim)}
                  </TableCell>

                  <TableCell className="px-4 font-medium">
                    <EstadoBadge estado={anoLectivo.estado} />
                  </TableCell>

                  {hasAnyAction && (
                    <TableCell className="px-4 text-right">
                      {(anoLectivo.can.update || anoLectivo.can.delete) && (
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

                          <DropdownMenuContent align="end">
                            {anoLectivo.can.update && (
                              <DropdownMenuItem
                                onClick={(e) => {
                                  e.stopPropagation();
                                  router.visit(edit(anoLectivo.id).url);
                                }}
                              >
                                Editar
                              </DropdownMenuItem>
                            )}

                            {anoLectivo.can.update && anoLectivo.can.delete && (
                              <DropdownMenuSeparator />
                            )}

                            {anoLectivo.can.delete && (
                              <DropdownMenuItem
                                variant="destructive"
                                onClick={(e) => {
                                  e.stopPropagation();
                                  deleteFn(anoLectivo.id);
                                }}
                              >
                                Remover
                              </DropdownMenuItem>
                            )}
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
  );
}
