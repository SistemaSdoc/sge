import { Head, Link } from '@inertiajs/react';
import { ArrowLeftIcon, CalendarDaysIcon, CreditCardIcon, ReceiptTextIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { index } from '@/actions/App/Http/Controllers/AvisoController';

const statusMap = {
  pago: { label: 'Pago', variant: 'default' },
  pendente: { label: 'Pendente', variant: 'secondary' },
  atrasado: { label: 'Atrasado', variant: 'destructive' },
};

const formatCurrency = (value) => {
  const amount = Number(value ?? 0);

  return Number.isNaN(amount)
    ? '—'
    : `${amount.toLocaleString('pt-MZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} MZN`;
};

export default function Show({ pagamento }) {
  const status = statusMap[pagamento?.estado] ?? statusMap.pendente;

  return (
    <div className="mx-auto w-full max-w-6xl px-6 py-6">
      <Head title={pagamento?.estudante ? `Pagamento • ${pagamento.estudante}` : 'Pagamento'} />

      <div className="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <p className="text-sm font-medium text-muted-foreground">Detalhes do pagamento</p>
          <h1 className="text-2xl font-semibold tracking-tight">
            {pagamento?.estudante ?? 'Pagamento escolar'}
          </h1>
        </div>

        <Button asChild variant="outline">
          <Link href={index().url}>
            <ArrowLeftIcon data-icon="inline-start" />
            Voltar à lista
          </Link>
        </Button>
      </div>

      <div className="grid gap-6 lg:grid-cols-[1.3fr_0.7fr]">
        <Card>
          <CardHeader>
            <div className="flex flex-wrap items-center justify-between gap-3">
              <div>
                <CardTitle>{pagamento?.referencia ?? 'Referência não informada'}</CardTitle>
                <CardDescription>{pagamento?.descricao ?? 'Sem descrição adicional.'}</CardDescription>
              </div>
              <Badge variant={status.variant}>{status.label}</Badge>
            </div>
          </CardHeader>

          <CardContent className="grid gap-4 text-sm text-muted-foreground">
            <div className="flex items-center gap-2">
              <ReceiptTextIcon className="size-4" />
              <span>Tipo: {pagamento?.tipo ?? 'Propina'}</span>
            </div>
            <div className="flex items-center gap-2">
              <CreditCardIcon className="size-4" />
              <span>Método: {pagamento?.metodo ?? 'Não definido'}</span>
            </div>
            <div className="flex items-center gap-2">
              <CalendarDaysIcon className="size-4" />
              <span>Data: {pagamento?.data ?? '—'}</span>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Resumo</CardTitle>
            <CardDescription>Informação resumida do registo.</CardDescription>
          </CardHeader>

          <CardContent className="space-y-4">
            <div>
              <p className="text-sm text-muted-foreground">Valor</p>
              <p className="text-2xl font-semibold">{formatCurrency(pagamento?.valor)}</p>
            </div>
            <div>
              <p className="text-sm text-muted-foreground">Estudante</p>
              <p className="font-medium">{pagamento?.estudante ?? '—'}</p>
            </div>
            <div>
              <p className="text-sm text-muted-foreground">Observações</p>
              <p className="font-medium">{pagamento?.observacoes ?? 'Sem observações.'}</p>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
