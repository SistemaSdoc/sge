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
  valorDoMes, // função síncrona: (itemId, mes, ano) => { valor_base, multa, valor } | null
}) {
  const lines = entries
    .map((entry) => {
      const item = feeItems.find((f) => f.id === entry.item_pagavel_id);
      if (!item) return null;

      if (item.frequencia === 'mensal') {
        const detalhesPorMes = entry.meses.map((mes) => {
          const info = valorDoMes ? valorDoMes(item.id, mes, entry.ano) : null;
          return {
            mes,
            valorBase: info ? Number(info.valor_base) : Number(item.valor),
            multa: info ? Number(info.multa) : 0,
            valor: info ? Number(info.valor) : Number(item.valor),
          };
        });

        // Subtotal = soma dos valores totais (base + multa) de cada mês
        const subtotal = detalhesPorMes.reduce((soma, d) => soma + d.valor, 0);
        const multaTotal = detalhesPorMes.reduce((soma, d) => soma + d.multa, 0);
        const baseTotal = detalhesPorMes.reduce((soma, d) => soma + d.valorBase, 0);

        return { entry, item, subtotal, multaTotal, baseTotal, detalhesPorMes };
      }

      // Itens não-mensais
      return {
        entry,
        item,
        subtotal: Number(item.valor),
        multaTotal: 0,
        baseTotal: Number(item.valor),
        detalhesPorMes: [],
      };
    })
    .filter((line) => line !== null);

  // Totais gerais
  const total = lines.reduce((sum, line) => sum + line.subtotal, 0);
  const multaTotalGeral = lines.reduce((sum, line) => sum + line.multaTotal, 0);
  const baseTotalGeral = lines.reduce((sum, line) => sum + line.baseTotal, 0);

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
          lines.map(({ entry, item, subtotal, multaTotal, baseTotal, detalhesPorMes }) => (
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

              {/* Detalhamento por mês (só para mensais) */}
              {item.frequencia === 'mensal' && detalhesPorMes.length > 0 && (
                <div className="ml-2 space-y-0.5 text-xs text-muted-foreground">
                  {detalhesPorMes.map((d) => (
                    <div key={d.mes} className="flex items-center gap-2">
                      <span className="w-8 font-medium">{MONTH_LABELS[d.mes - 1]}</span>
                      <span>Base: {formatMoney(d.valorBase)}</span>
                      {d.multa > 0 && (
                        <>
                          <span className=" ">+ Multa: {formatMoney(d.multa)}</span>
                          <span>= {formatMoney(d.valor)}</span>
                        </>
                      )}
                      {d.multa === 0 && <span>= {formatMoney(d.valor)}</span>}
                    </div>
                  ))}
               
                </div>
              )}

              {/* Para itens não-mensais, mostra apenas o valor */}
              {item.frequencia !== 'mensal' && (
                <div className="ml-2 text-xs text-muted-foreground">
                  Valor: {formatMoney(item.valor)}
                </div>
              )}
            </div>
          ))
        )}

        <Separator />

        {/* Resumo geral */}
        <div className="space-y-1 text-sm">
          <div className="flex items-center justify-between">
            <span>Propina</span>
            <span className="tabular-nums">{formatMoney(baseTotalGeral)}</span>
          </div>
          {multaTotalGeral > 0 && (
            <div className="flex items-center justify-between  ">
              <span>Multa</span>
              <span className="tabular-nums">{formatMoney(multaTotalGeral)}</span>
            </div>
          )}
          <div className="flex items-center justify-between border-t pt-1 text-base font-bold">
            <span>Total a pagar</span>
            <span className="tabular-nums">{formatMoney(total)}</span>
          </div>
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