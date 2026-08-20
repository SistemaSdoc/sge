import { Head, Link, router } from '@inertiajs/react';
import {
  ArrowLeftIcon,
  CalendarDaysIcon,
  CreditCardIcon,
  UserIcon,
  ReceiptTextIcon,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import {
  Table,
  TableBody,
  TableCell,
  TableFooter,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  index,
  show,
} from '@/actions/App/Http/Controllers/Tenant/PagamentoController';
import { MONTH_LABELS } from '@/lib/pagamentos';
import TablePagination from '@/components/table-pagination';

const formatCurrency = (value) => {
  const amount = Number(value ?? 0);
  return Number.isNaN(amount)
    ? '—'
    : `${amount.toLocaleString('pt', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} AOA`;
};

const metodoLabels = {
  dinheiro: 'Dinheiro',
  transferencia: 'Transferência',
  multicaixa: 'Multicaixa',
  outro: 'Outro',
};

const frequenciaLabels = {
  mensal: 'Mensal',
  anual: 'Anual',
  unico: 'Único',
};

function periodoLabel(frequencia, mes, ano) {
  if (frequencia === 'mensal') return `${MONTH_LABELS[mes - 1]} ${ano}`;
  if (frequencia === 'anual') return `Ano ${ano}`;
  return `${ano}`;
}

export default function Show({ pagamento }) {
  const itens = pagamento.itens.data;
  const temAlgumaMulta = itens.some((item) => Number(item.multa) > 0);
  const multaTotalPagina = itens.reduce((soma, item) => soma + Number(item.multa ?? 0), 0);

  function handlePageChange(page) {
    router.reload({
      only: ['pagamento'],
      data: { page },
      preserveScroll: true,
      preserveState: true,
    });
  }

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <Head title={`Pagamento de ${pagamento.aluno}`} />

      <div className="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 className="text-xl font-semibold tracking-tight">
            Detalhes do pagamento
          </h1>
          <p className="text-sm text-muted-foreground">
            Pagamento realizado por {pagamento.aluno}
          </p>
        </div>
        <Button asChild variant="outline" size={'sm'}>
          <Link href={index().url}>
            <ArrowLeftIcon data-icon="inline-start" />
            Voltar à lista
          </Link>
        </Button>
      </div>

      <div className="grid gap-6 lg:grid-cols-[1fr_300px]">
        {/* Itens pagos */}
        <Card className="gap-0 pb-0">
          <CardHeader className="border-b">
            <CardTitle>Itens pagos</CardTitle>
            <CardDescription>
              {itens.length} {itens.length === 1 ? 'item' : 'itens'} nesta
              página
            </CardDescription>
          </CardHeader>

          <CardContent className="p-0">
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/50">
                  <TableHead className="px-4">Item</TableHead>
                  <TableHead className="px-4">Frequência</TableHead>
                  <TableHead className="px-4">Período</TableHead>
                  {temAlgumaMulta && (
                    <TableHead className="px-4 text-right">Multa</TableHead>
                  )}
                  <TableHead className="px-4 text-right">Valor</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {itens.map((item) => (
                  <TableRow key={item.id}>
                    <TableCell className="px-4 font-medium">
                      {item.nome}
                    </TableCell>
                    <TableCell className="px-4">
                      <Badge variant="secondary">
                        {frequenciaLabels[item.frequencia] ?? item.frequencia}
                      </Badge>
                    </TableCell>
                    <TableCell className="px-4 text-muted-foreground">
                      {periodoLabel(item.frequencia, item.mes, item.ano)}
                    </TableCell>
                    {temAlgumaMulta && (
                      <TableCell className="px-4 text-right">
                        {Number(item.multa) > 0 ? (
                          <span className="inline-flex items-center gap-1 font-medium">
                         
                            {formatCurrency(item.multa)}
                          </span>
                        ) : (
                          <span className="text-muted-foreground">—</span>
                        )}
                      </TableCell>
                    )}
                    <TableCell className="px-4 text-right font-medium tabular-nums">
                      {formatCurrency(item.valor)}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>

              <TableFooter>
          
                <TableRow>
                  <TableCell colSpan={temAlgumaMulta ? 3 : 3} className="px-4">
                    Total
                  </TableCell>
                  <TableCell className="px-4 text-right">
                    {formatCurrency(pagamento.valor_total)}
                  </TableCell>
                </TableRow>
              </TableFooter>
            </Table>
          </CardContent>

          <TablePagination
            pagination={{
              current_page: pagamento.itens.current_page,
              last_page: pagamento.itens.last_page,
            }}
            onPageChange={handlePageChange}
          />
        </Card>

        {/* Resumo */}
        <Card className="h-fit">
          <CardHeader>
            <CardTitle>Resumo</CardTitle>
          </CardHeader>
          <CardContent className="flex flex-col gap-3 text-sm">
            <div className="flex items-center gap-2 text-muted-foreground">
              Aluno(a):
              <span>{pagamento.aluno}</span>
            </div>

            <div className="flex items-center gap-2 text-muted-foreground">
              Realizado em:
              <span>{pagamento.data_pagamento}</span>
            </div>

            <div className="flex items-center gap-2 text-muted-foreground">
              Método de pagamento:
              <span>{metodoLabels[pagamento.metodo] ?? pagamento.metodo}</span>
            </div>

            {pagamento.referencia && (
              <div className="flex items-center gap-2 text-muted-foreground">
                <ReceiptTextIcon className="size-4 shrink-0" />
                <span>{pagamento.referencia}</span>
              </div>
            )}

            <div className="flex items-center justify-between">
              <p className="text-muted-foreground">Registado por: </p>
              <p className="font-medium">{pagamento.registado_por}</p>
            </div>

            <Separator />

                  {temAlgumaMulta && (

                    <div className="flex items-center justify-between">
              <span className="text-muted-foreground">  Total em multas</span>
              <span className="text-lg font-bold tabular-nums">
              {formatCurrency(multaTotalPagina)}
              </span>
            </div>

                )}

            <div className="flex items-center justify-between">
              <span className="text-muted-foreground">Total</span>
              <span className="text-lg font-bold tabular-nums">
                {formatCurrency(pagamento.valor_total)}
              </span>
            </div>

            {pagamento.observacoes && (
              <>
                <Separator />
                <div>
                  <p className="mb-1 text-muted-foreground">Observações</p>
                  <p>{pagamento.observacoes}</p>
                </div>
              </>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}