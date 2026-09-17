import { Head, Link } from '@inertiajs/react';
import { Clock, FileText, CheckCircle, XCircle, AlertCircle } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

export default function Index({ justificativas }) {
  const getStatusIcon = (statusLabel) => {
    if (statusLabel === 'Aceite') return <CheckCircle className="size-4 text-green-600" />;
    if (statusLabel === 'Recusada') return <XCircle className="size-4 text-red-600" />;
    return <AlertCircle className="size-4 text-yellow-600" />;
  };

  return (
    <>
      <Head title="Minhas Justificativas" />
      <div className="max-w-5xl mx-auto p-6">
        <div className="flex items-center justify-between mb-6">
          <h1 className="text-2xl font-bold">Minhas Justificativas</h1>
          <Link
            href="/professor/provas"
            className="text-sm text-muted-foreground hover:text-foreground transition-colors"
          >
            ← Voltar para provas
          </Link>
        </div>

        {justificativas.length === 0 ? (
          <Card className="shadow-sm">
            <CardContent className="flex flex-col items-center justify-center py-12 text-muted-foreground">
              <FileText className="size-12 mb-3 opacity-50" />
              <p className="text-lg font-medium">Nenhuma justificativa enviada</p>
              <p className="text-sm">Você ainda não enviou justificativas para nenhum prazo.</p>
            </CardContent>
          </Card>
        ) : (
          <Card className="shadow-sm">
            <CardHeader>
              <CardTitle>Histórico de Justificativas</CardTitle>
              <CardDescription>
                Lista de todas as justificativas de não submissão que você enviou.
              </CardDescription>
            </CardHeader>
            <CardContent className="p-0">
              <Table>
                <TableHeader>
                  <TableRow className="bg-muted/50">
                    <TableHead className="px-4">Prazo</TableHead>
                    <TableHead className="px-4">Motivo</TableHead>
                    <TableHead className="px-4 text-center">Status</TableHead>
                    <TableHead className="px-4 text-center">Data</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {justificativas.map((just) => (
                    <TableRow key={just.id}>
                      <TableCell className="px-4 font-medium">{just.prazo_titulo}</TableCell>
                      <TableCell className="px-4 max-w-xs truncate" title={just.motivo}>
                        {just.motivo}
                      </TableCell>
                      <TableCell className="px-4 text-center">
                        <Badge
                          variant="outline"
                          className={
                            just.status_label === 'Aceite'
                              ? 'border-green-500 text-green-700'
                              : just.status_label === 'Recusada'
                              ? 'border-red-500 text-red-700'
                              : 'border-yellow-500 text-yellow-700'
                          }
                        >
                          <span className="flex items-center gap-1">
                            {getStatusIcon(just.status_label)}
                            {just.status_label}
                          </span>
                        </Badge>
                      </TableCell>
                      <TableCell className="px-4 text-center">{just.data}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        )}
      </div>
    </>
  );
}