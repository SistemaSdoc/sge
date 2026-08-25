import { Head } from '@inertiajs/react';
import { DownloadIcon, CheckCircle2Icon, AlertCircleIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  CardAction,
} from '@/components/ui/card';
import {
  Table,
  TableHeader,
  TableRow,
  TableHead,
  TableBody,
  TableCell,
} from '@/components/ui/table';
import { EmptyState } from '@/components/empty-state';
import { pdf } from '@/actions/App/Http/Controllers/Tenant/RelatorioPropinaController';

const formatCurrency = (value) => {
  const amount = Number(value ?? 0);
  return `${amount.toLocaleString('pt', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} AOA`;
};

const corBadge = (total) => {
  if (total >= 3) return 'destructive';
  if (total === 2) return 'default';
  return 'secondary';
};

export default function RelatorioPropinasTurma({
  turma,
  linhas,
  emDia,
  resumo,
  geradoEm,
}) {
  return (
    <div className="mx-auto w-full max-w-5xl space-y-4 p-6">
      <Head title={`Situação de propinas — ${turma.nome}`} />

      <Card>
        <CardHeader className="border-b">
          <CardTitle>Situação de propinas</CardTitle>
          <CardDescription>
            {turma.curso} — {turma.classe} — Turma {turma.nome} ({turma.turno})
            · {turma.ano_lectivo}
          </CardDescription>

          <CardAction>
            <Button variant="outline" size="sm" asChild>
              <a href={pdf(turma.id).url} target="_blank" rel="noopener">
                <DownloadIcon className="mr-1 size-3" /> Exportar PDF
              </a>
            </Button>
          </CardAction>
        </CardHeader>

        <CardContent className="p-0!">
          <div className="grid grid-cols-5 divide-x border-b bg-muted/40 text-center">
            <div className="py-3">
              <p className="text-2xl font-bold">{resumo.total_alunos}</p>
              <p className="text-xs text-muted-foreground">Alunos na turma</p>
            </div>
            <div className="py-3">
              <p className="text-2xl font-bold">{resumo.total_devedores}</p>
              <p className="text-xs text-muted-foreground">Não regularizada</p>
            </div>
            <div className="py-3">
              <p className="text-2xl font-bold">{resumo.total_em_dia}</p>
              <p className="text-xs text-muted-foreground">Reguralizado(s)</p>
            </div>
            <div className="py-3">
              <p className="text-2xl font-bold">
                {formatCurrency(resumo.multa_total_geral)}
              </p>
              <p className="text-xs text-muted-foreground">Total em multas</p>
            </div>
            <div className="py-3">
              <p className="text-2xl font-bold">
                {formatCurrency(resumo.valor_total_geral)}
              </p>
              <p className="text-xs text-muted-foreground">Total em dívida</p>
            </div>
          </div>

          <div className="border-b px-4 py-3">
            <p className="text-sm font-medium">
              Alunos com situação financeira não regularizada
            </p>
          </div>

          {linhas.length === 0 ? (
            <EmptyState
              icon={CheckCircle2Icon}
              title="Nenhum aluno com situação financeira regularizada"
              description="Todos os alunos desta turma estão com as propinas em dia."
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/72">
                  <TableHead className="px-4">Aluno</TableHead>
                  <TableHead className="px-4 text-center">
                    Meses em falta
                  </TableHead>
                  <TableHead className="px-4">Meses</TableHead>
                  <TableHead className="px-4 text-right">Multa</TableHead>
                  <TableHead className="px-4 text-right">
                    Valor devido
                  </TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {linhas.map((linha) => (
                  <TableRow key={linha.aluno_id}>
                    <TableCell className="px-4 font-medium">
                      {linha.nome}
                    </TableCell>
                    <TableCell className="px-4 text-center">
                      <Badge variant={corBadge(linha.total_meses)}>
                        {linha.total_meses}
                      </Badge>
                    </TableCell>
                    <TableCell className="px-4 text-sm text-muted-foreground">
                      <div className="flex flex-wrap gap-1">
                        {linha.meses.map((m, i) => (
                          <span
                            key={i}
                            className="inline-flex items-center gap-1"
                          >
                            {m.label}
                            {m.com_multa && (
                              <AlertCircleIcon className="size-3" />
                            )}
                            {i < linha.meses.length - 1 && ','}
                          </span>
                        ))}
                      </div>
                    </TableCell>
                    <TableCell className="px-4 text-right">
                      {linha.multa_total > 0 ? (
                        <span className="font-medium">
                          {formatCurrency(linha.multa_total)}
                        </span>
                      ) : (
                        <span className="text-muted-foreground">—</span>
                      )}
                    </TableCell>
                    <TableCell className="px-4 text-right font-medium">
                      {formatCurrency(linha.valor_total)}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="border-b">
          <CardTitle className="text-base">
            Alunos com situação financeira regularizada
          </CardTitle>
          <CardDescription>
            {emDia.length} aluno{emDia.length === 1 ? '' : 's'} sem pendências
            nesta turma.
          </CardDescription>
        </CardHeader>

        <CardContent className="p-0!">
          {emDia.length === 0 ? (
            <EmptyState
              icon={CheckCircle2Icon}
              title="Nenhum Reguralizado"
              description="Ainda não há alunos com as propinas totalmente pagas nesta turma."
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/72">
                  <TableHead className="px-4">Aluno</TableHead>
                  <TableHead className="px-4 text-right">Situação</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {emDia.map((aluno) => (
                  <TableRow key={aluno.aluno_id}>
                    <TableCell className="px-4 font-medium">
                      {aluno.nome}
                    </TableCell>
                    <TableCell className="px-4 text-right">
                      <Badge
                        variant="secondary"
                        className="bg-green-100 text-green-700 hover:bg-green-100"
                      >
                        Regularizada
                      </Badge>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      <p className="text-xs text-muted-foreground">Gerado em {geradoEm}</p>
    </div>
  );
}
