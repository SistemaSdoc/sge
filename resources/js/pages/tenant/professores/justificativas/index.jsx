import { Head, Link } from '@inertiajs/react';
import {
  FileText,
  CheckCircle,
  XCircle,
  AlertCircle,
  Users,
} from 'lucide-react';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Badge } from '@/components/ui/badge';
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
import { EmptyState } from '@/components/empty-state';

export default function Index({ justificativas = [] }) {
  const statusBadge = (statusLabel) => {
    if (statusLabel === 'Aceite') {
      return (
        <Badge variant="outline" className="border-green-500 text-green-700">
          <CheckCircle className="mr-1 size-3" />
          Aceite
        </Badge>
      );
    }
    if (statusLabel === 'Recusada') {
      return (
        <Badge variant="outline" className="border-red-500 text-red-700">
          <XCircle className="mr-1 size-3" />
          Recusada
        </Badge>
      );
    }
    return (
      <Badge variant="outline" className="border-amber-500 text-amber-700">
        <AlertCircle className="mr-1 size-3" />
        {statusLabel}
      </Badge>
    );
  };

  return (
    <>
      <Head title="Minhas Justificativas" />

      <div className="mx-auto w-full max-w-7xl space-y-4 p-4 sm:p-6">
        {/* Header + Tabela no mesmo Card */}
        <Card className="gap-0 pb-0">
          <CardHeader className="flex flex-col gap-3 border-b border-foreground/10 md:flex-row md:items-center">
            <div className="min-w-0 md:flex-1">
              <Breadcrumb>
                <BreadcrumbList className="flex-wrap">
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbLink asChild>
                      <Link
                        href="/dashboard/professor/provas"
                        className="line-clamp-2 text-sm font-semibold text-primary"
                      >
                        Provas
                      </Link>
                    </BreadcrumbLink>
                  </BreadcrumbItem>
                  <BreadcrumbSeparator />
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbPage className="line-clamp-2 text-sm font-semibold text-secondary">
                      Minhas Justificativas
                    </BreadcrumbPage>
                  </BreadcrumbItem>
                </BreadcrumbList>
              </Breadcrumb>
              <CardDescription>
                Histórico de todas as justificativas de não submissão que
                enviou.
              </CardDescription>
            </div>
            <CardAction>
              <Badge variant="secondary">
                {justificativas.length}{' '}
                {justificativas.length === 1 ? 'justificativa' : 'justificativas'}
              </Badge>
            </CardAction>
          </CardHeader>

          <CardContent className="p-0!">
            {justificativas.length === 0 ? (
              <EmptyState
                variant="table"
                icon={FileText}
                title="Nenhuma justificativa enviada"
                description="Ainda não enviou justificativas para nenhum prazo."
              />
            ) : (
              <div className="w-full overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow className="bg-muted/72">
                      <TableHead className="min-w-48 px-4">Prazo</TableHead>
                      <TableHead className="min-w-32 px-4">Turma</TableHead>
                      <TableHead className="min-w-64 px-4">Motivo</TableHead>
                      <TableHead className="min-w-32 px-4 text-center">
                        Estado
                      </TableHead>
                      <TableHead className="min-w-28 px-4 text-center">
                        Data
                      </TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {justificativas.map((just) => (
                      <TableRow key={just.id}>
                        <TableCell className="px-4 font-medium">
                          <span className="line-clamp-2">
                            {just.prazo_titulo}
                          </span>
                        </TableCell>
                        <TableCell className="px-4">
                          {just.turma_nome ? (
                            <span className="flex items-center gap-1 text-sm font-medium text-primary">
                              <Users className="size-3.5 shrink-0" />
                              <span className="line-clamp-1">
                                {just.turma_nome}
                              </span>
                            </span>
                          ) : (
                            <span className="text-xs text-muted-foreground">
                              —
                            </span>
                          )}
                        </TableCell>
                        <TableCell
                          className="px-4 max-w-xs"
                          title={just.motivo}
                        >
                          <span className="line-clamp-2 text-sm">
                            {just.motivo}
                          </span>
                        </TableCell>
                        <TableCell className="px-4 text-center">
                          {statusBadge(just.status_label)}
                        </TableCell>
                        <TableCell className="px-4 text-center text-xs text-muted-foreground whitespace-nowrap">
                          {just.data}
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </>
  );
}