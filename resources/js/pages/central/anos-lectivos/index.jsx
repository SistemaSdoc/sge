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
  create,
  index,
  archive,
  restore,
} from '@/actions/App/Http/Controllers/Central/AnoLectivoController';
import { useDialog } from '@/hooks/use-dialog';

function formatarData(data) {
  return new Intl.DateTimeFormat('pt-PT', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(data));
}

const ESTADO_CONFIG = {
  planeado: {
    label: 'Planeado',
    dot: 'bg-blue-500',
    text: 'text-blue-700 dark:text-blue-400',
    pulse: false,
  },
  matriculas_abertas: {
    label: 'Matrículas Abertas',
    dot: 'bg-amber-500',
    text: 'text-amber-700 dark:text-amber-400',
    pulse: true,
  },
  em_curso: {
    label: 'Em Curso',
    dot: 'bg-emerald-500',
    text: 'text-emerald-700 dark:text-emerald-400',
    pulse: true,
  },
  encerrado: {
    label: 'Encerrado',
    dot: 'bg-muted-foreground/50',
    text: 'text-muted-foreground',
    pulse: false,
  },
};

function EstadoBadge({ estado }) {
  const config = ESTADO_CONFIG[estado] ?? ESTADO_CONFIG.encerrado;

  return (
    <span
      className={`inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium ${config.text}`}
    >
      <span className="relative flex size-1.5">
        {config.pulse && (
          <span
            className={`absolute inline-flex h-full w-full animate-ping ${config.dot} opacity-75`}
          />
        )}
        <span className={`relative inline-flex size-1.5 ${config.dot}`} />
      </span>
      {config.label}
    </span>
  );
}

export default function Index({ anosLectivos }) {
  const { confirm, deleteConfirm, alert, closeDialog } = useDialog();

  const handleArchive = (anoLectivo) => {
    deleteConfirm({
      title: 'Arquivar ano lectivo?',
      description: `O ano lectivo ${anoLectivo.nome} será arquivado e poderá ser restaurado mais tarde.`,
      confirmLabel: 'Arquivar',
      confirmFn: () =>
        router.post(
          archive(anoLectivo.id).url,
          {},
          {
            onError: (errors) => {
              const message =
                errors?.ano_inicio ??
                `O ano lectivo ${anoLectivo.nome} não pode ser arquivado porque está activo e é a referência central actualmente usada pelos tenants. O arquivamento ficará disponível quando este ano deixar de estar activo; a mudança para o próximo ano é automática.`;

              closeDialog();
              alert({
                title: 'Ano lectivo não pode ser arquivado',
                description: message,
                confirmLabel: 'Entendi',
              });
            },
          },
        ),
    });
  };

  const handleRestore = (anoLectivo) => {
    confirm({
      title: 'Restaurar ano lectivo?',
      description: `O ano lectivo ${anoLectivo.nome} voltará a estar disponível na gestão central.`,
      confirmLabel: 'Restaurar',
      confirmFn: () => router.post(restore(anoLectivo.id).url),
    });
  };

  const handlePageChange = (page) => {
    router.visit(index().url, {
      data: { page },
      preserveScroll: true,
    });
  };

  return (
    <>
      <Head title="Anos Lectivos" />
      <div className="mx-auto w-full max-w-7xl p-6">
        <Card className="gap-0 pb-0">
          <CardHeader className="border-b">
            <CardTitle>Anos Lectivos</CardTitle>
            <CardDescription>
              Gestão central dos anos lectivos sincronizados com as
              instituições.
            </CardDescription>

            <CardAction>
              <Button asChild>
                <Link href={create().url}>Adicionar ano lectivo</Link>
              </Button>
            </CardAction>
          </CardHeader>

          <CardContent className="p-0!">
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/72">
                  <TableHead className="px-4">Nome</TableHead>
                  <TableHead>Data início</TableHead>
                  <TableHead>Data fim</TableHead>
                  <TableHead className="text-center">Estado</TableHead>
                  <TableHead className="text-center">Activo</TableHead>
                  <TableHead className="px-4 text-right">Acções</TableHead>
                </TableRow>
              </TableHeader>

              <TableBody>
                {anosLectivos.data.map((anoLectivo) => (
                  <TableRow key={anoLectivo.id}>
                    <TableCell className="px-4 font-medium">
                      {anoLectivo.nome}
                    </TableCell>
                    <TableCell>
                      {formatarData(anoLectivo.data_inicio)}
                    </TableCell>
                    <TableCell>{formatarData(anoLectivo.data_fim)}</TableCell>
                    <TableCell className="text-center">
                      <EstadoBadge estado={anoLectivo.estado} />
                    </TableCell>
                    <TableCell className="text-center">
                      {anoLectivo.activo ? (
                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:text-emerald-400">
                          <span className="relative flex size-1.5">
                            <span className="absolute inline-flex h-full w-full animate-ping bg-emerald-500 opacity-75" />
                            <span className="relative inline-flex size-1.5 bg-emerald-500" />
                          </span>
                          Sim
                        </span>
                      ) : (
                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium text-muted-foreground">
                          <span className="relative flex size-1.5">
                            <span className="relative inline-flex size-1.5 bg-muted-foreground/50" />
                          </span>
                          Não
                        </span>
                      )}
                    </TableCell>
                    <TableCell className="px-4 text-right">
                      <div className="flex justify-end gap-2">
                        {anoLectivo.deleted_at ? (
                          <Button
                            variant="outline"
                            size="xs"
                            onClick={() => handleRestore(anoLectivo)}
                          >
                            Restaurar
                          </Button>
                        ) : (
                          <Button
                            variant="destructive"
                            size="xs"
                            onClick={() => handleArchive(anoLectivo)}
                          >
                            Arquivar
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
            pagination={anosLectivos}
            onPageChange={handlePageChange}
          />
        </Card>
      </div>
    </>
  );
}
