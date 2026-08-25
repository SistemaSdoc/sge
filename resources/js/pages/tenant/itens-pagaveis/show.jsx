import { Head, Link } from '@inertiajs/react';
import { ArrowLeftIcon } from 'lucide-react';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardDescription,
} from '@/components/ui/card';
import { index } from '@/actions/App/Http/Controllers/Tenant/ItemPagavelController';

const formatCurrency = (value) => {
  const amount = Number(value ?? 0);
  return Number.isNaN(amount)
    ? '—'
    : `${amount.toLocaleString('pt-MZ', { minimumFractionDigits: 2 })} MZN`;
};

export default function Show({ itemPagavel }) {
  return (
    <div className="mx-auto w-full max-w-4xl px-6 py-6">
      <Head title={itemPagavel?.nome ?? 'Item pagável'} />

      <div className="mb-6 flex items-center justify-between">
        <div>
          <p className="text-sm text-muted-foreground">Item pagável</p>
          <h1 className="text-2xl font-semibold">{itemPagavel?.nome}</h1>
        </div>

        <Link href={index().url} className="inline-flex items-center">
          <ArrowLeftIcon className="size-4" />
          <span className="ml-2">Voltar</span>
        </Link>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Detalhes</CardTitle>
            <CardDescription>Informação do item pagável</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div>
              <p className="text-sm text-muted-foreground">Tipo</p>
              <p className="font-medium">{itemPagavel?.tipo}</p>
            </div>

            <div>
              <p className="text-sm text-muted-foreground">Valor padrão</p>
              <p className="font-medium">
                {formatCurrency(itemPagavel?.valor_padrao)}
              </p>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
