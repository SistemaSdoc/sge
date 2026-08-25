import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Item,
  ItemActions,
  ItemContent,
  ItemDescription,
  ItemFooter,
  ItemTitle,
} from '@/components/ui/item';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { formatMoney, frequencyLabel, MONTH_LABELS } from '@/lib/pagamentos';
import { cn } from '@/lib/utils';
import { TriangleAlert } from 'lucide-react';

export function CartItem({
  item,
  selected,
  selectedMonths,
  paidMonths,
  disabled = false,
  onToggle,
  onMonthsChange,
  valorDoMes,
}) {
  const isMonthly = item.frequencia === 'mensal';

  const normalizedPaidMonths = (paidMonths ?? [])
    .map((month) => Number(month))
    .filter((month) => Number.isFinite(month));

  const uniquePaidMonths = [...new Set(normalizedPaidMonths)];

  const isFullyPaid =
    isMonthly && MONTH_LABELS.length === uniquePaidMonths.length;

  const alreadyPaid =
    (!isMonthly && uniquePaidMonths.length > 0) || isFullyPaid;

  const isDisabled = disabled || alreadyPaid;

  // Meses seleccionados que têm multa aplicável, segundo o backend.
  const mesesComMulta =
    isMonthly && valorDoMes
      ? selectedMonths.filter((mes) => {
          const info = valorDoMes(mes);
          return info && Number(info.multa) > 0;
        })
      : [];

  return (
    <Item variant="outline" className={cn(isDisabled && 'opacity-60')}>
      <ItemActions>
        <Checkbox
          id={`fee-${item.id}`}
          checked={selected}
          disabled={isDisabled}
          onCheckedChange={(checked) => onToggle(checked === true)}
        />
      </ItemActions>

      <ItemContent>
        <ItemTitle>
          <label htmlFor={`fee-${item.id}`} className="cursor-pointer">
            {item.nome}
          </label>
        </ItemTitle>

        <ItemDescription className="flex flex-wrap items-center gap-2">
          <Badge variant="default" className="p-1 text-[10px]">
            {frequencyLabel(item.frequencia)}
          </Badge>

          {alreadyPaid && (
            <Badge variant="outline" className="p-1 text-[10px]">
              {isMonthly && isFullyPaid ? 'Todos os meses pagos' : 'Já pago'}
            </Badge>
          )}

          {isMonthly && item.multa_dias_tolerancia && item.multa_valor && (
            <Badge variant="outline" className="gap-1 p-1 text-[10px]">
              Multa após dia {item.multa_dias_tolerancia}
            </Badge>
          )}

          {item.descricao && <span>{item.descricao}</span>}
        </ItemDescription>
      </ItemContent>

      <ItemActions>
        <span className="text-sm font-semibold tabular-nums">
          {formatMoney(item.valor)}
          {isMonthly && (
            <span className="font-normal text-muted-foreground">/mês</span>
          )}
        </span>
      </ItemActions>

      {isMonthly && selected && (
        <ItemFooter className="flex flex-col gap-1.5">
          <ToggleGroup
            type="multiple"
            variant="outline"
            size="sm"
            value={selectedMonths.map(String)}
            onValueChange={(value) =>
              onMonthsChange(value.map(Number).sort((a, b) => a - b))
            }
            className="flex-wrap"
            aria-label={`Meses para ${item.nome}`}
          >
            {MONTH_LABELS.map((label, index) => {
              const month = index + 1;
              const isPaid = paidMonths.includes(month);
              const info = valorDoMes ? valorDoMes(month) : null;
              const temMulta = info && Number(info.multa) > 0;

              return (
                <ToggleGroupItem
                  key={month}
                  value={String(month)}
                  disabled={isPaid}
                  className={cn(
                    'min-w-11',
                    isPaid && 'line-through',
                    temMulta && 'border-destructive/50',
                  )}
                >
                  {label}
                  {temMulta && '*'}
                </ToggleGroupItem>
              );
            })}
          </ToggleGroup>

          {mesesComMulta.length > 0 && (
            <p className="flex items-center gap-1 text-xs">
              Mês com multa por atraso incluída no valor
            </p>
          )}
        </ItemFooter>
      )}
    </Item>
  );
}
