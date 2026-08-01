import { Head } from '@inertiajs/react';
import { DownloadIcon, CheckCircle2Icon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Card, CardHeader, CardTitle, CardDescription, CardContent, CardAction,
} from '@/components/ui/card';
import {
  Table, TableHeader, TableRow, TableHead, TableBody, TableCell,
} from '@/components/ui/table';
import { EmptyState } from '@/components/empty-state';
import { pdf } from  '@/actions/App/Http/Controllers/RelatorioPropinaController';
const formatCurrency = (value) => {
  const amount = Number(value ?? 0);
  return `${amount.toLocaleString('pt', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} AOA`;
};

const corBadge = (total) => {
  if (total >= 3) return 'destructive';
  if (total === 2) return 'default';
  return 'secondary';
};

export default function RelatorioPropinasTurma({ turma, linhas, resumo, geradoEm }) {
  return (
    <div className="mx-auto w-full max-w-5xl p-6 space-y-4">
      <Head title={`Propinas em atraso — ${turma.nome}`} />

      <Card>
        <CardHeader className="border-b">
          <CardTitle>Propinas em atraso</CardTitle>
          <CardDescription>
            {turma.curso} — {turma.classe} — Turma {turma.nome} ({turma.turno}) · {turma.ano_lectivo}
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
          <div className="grid grid-cols-3 divide-x border-b bg-muted/40 text-center">
            <div className="py-3">
              <p className="text-2xl font-bold">{resumo.total_alunos}</p>
              <p className="text-xs text-muted-foreground">Alunos na turma</p>
            </div>
            <div className="py-3">
              <p className="text-2xl font-bold">{resumo.total_devedores}</p>
              <p className="text-xs text-muted-foreground">Em atraso</p>
            </div>
            <div className="py-3">
              <p className="text-2xl font-bold">{formatCurrency(resumo.valor_total_geral)}</p>
              <p className="text-xs text-muted-foreground">Total em dívida</p>
            </div>
          </div>

          {linhas.length === 0 ? (
            <EmptyState
              icon={CheckCircle2Icon}
              title="Tudo em dia"
              description="Nenhum aluno desta turma tem propinas em atraso."
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/72">
                  <TableHead className="px-4">Aluno</TableHead>
                  <TableHead className="px-4 text-center">Meses em falta</TableHead>
                  <TableHead className="px-4">Meses</TableHead>
                  <TableHead className="px-4 text-right">Valor devido</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {linhas.map((linha) => (
                  <TableRow key={linha.aluno_id}>
                    <TableCell className="px-4 font-medium">{linha.nome}</TableCell>
                    <TableCell className="px-4 text-center">
                      <Badge variant={corBadge(linha.total_meses)}>{linha.total_meses}</Badge>
                    </TableCell>
                    <TableCell className="px-4 text-muted-foreground text-sm">
                      {linha.meses.map((m) => m.label).join(', ')}
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

      <p className="text-xs text-muted-foreground">Gerado em {geradoEm}</p>
    </div>
  );
}