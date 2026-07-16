import { XIcon, ShoppingCartIcon } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from '@/components/ui/empty';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { formatMoney, MONTH_LABELS } from '@/lib/pagamentos';

export function CartSummary({
  student,
  entries,
  feeItems,
  processing,
  onRemove,
  onSubmit,
}) {
  const lines = entries
    .map((entry) => {
      const item = feeItems.find((f) => f.id === entry.item_pagavel_id);
      if (!item) return null;
      const quantity = item.frequencia === 'mensal' ? entry.meses.length : 1;
      return { entry, item, subtotal: item.valor * quantity };
    })
    .filter((line) => line !== null);

  const total = lines.reduce((sum, line) => sum + line.subtotal, 0);

  const canSubmit =
    student !== null &&
    lines.length > 0 &&
    lines.every(
      (line) =>
        line.item.frequencia !== 'mensal' || line.entry.meses.length > 0,
    );

  return (
    <Card>
      <CardHeader>
        <CardTitle>Resumo do pagamento</CardTitle>
        <CardDescription>
          {student ? `Aluno: ${student.nome}` : 'Nenhum aluno selecionado'}
        </CardDescription>
      </CardHeader>
      <CardContent className="flex flex-col gap-3">
        {lines.length === 0 ? (
          <Empty className="border border-dashed py-8">
            <EmptyHeader>
              <EmptyMedia variant="icon">
                <ShoppingCartIcon />
              </EmptyMedia>
              <EmptyTitle>Sem itens adicionados</EmptyTitle>
              <EmptyDescription>
                Selecione os itens que o aluno(a) {student?.nome} pretende
                pagar.
              </EmptyDescription>
            </EmptyHeader>
          </Empty>
        ) : (
          lines.map(({ entry, item, subtotal }) => (
            <div key={item.id} className="flex flex-col gap-1">
              <div className="flex items-center justify-between gap-2">
                <span className="text-sm font-medium">{item.nome}</span>
                <div className="flex items-center gap-1">
                  <span className="text-sm tabular-nums">
                    {formatMoney(subtotal)}
                  </span>

                  <Button
                    variant="ghost"
                    size="icon-sm"
                    onClick={() => onRemove(item.id)}
                    aria-label={`Remover ${item.nome}`}
                  >
                    <XIcon />
                  </Button>
                </div>
              </div>
              {item.frequencia === 'mensal' && (
                <div className="flex flex-wrap gap-1">
                  {entry.meses.length === 0 ? (
                    <span className="text-xs text-destructive">
                      Selecione pelo menos um mês
                    </span>
                  ) : (
                    entry.meses.map((month) => (
                      <Badge key={month} variant="">
                        {MONTH_LABELS[month - 1]}
                      </Badge>
                    ))
                  )}
                </div>
              )}
            </div>
          ))
        )}

        <Separator />

        <div className="flex items-center justify-between">
          <span className="text-sm font-medium">Total</span>
          <span className="text-lg font-bold tabular-nums">
            {formatMoney(total)}
          </span>
        </div>
      </CardContent>

      <CardFooter className="border-nones">
        <Button
          className="w-full"
          disabled={!canSubmit || processing}
          onClick={onSubmit}
        >
          {processing && <Spinner data-icon="inline-start" />}
          {processing ? 'A processar...' : 'Confirmar pagamento'}
        </Button>
      </CardFooter>
    </Card>
  );
}
