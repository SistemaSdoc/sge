import {
  Card,
  CardAction,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';

export function ResumoCards({ resumo, tipo, filtroActivo, onFiltro }) {
  if (!resumo) return null;

  const cards = {
    trimestral: [
      {
        label: 'Total',
        filtro: null,
        value:
          (resumo.apto ?? 0) +
          (resumo.nao_apto ?? 0) +
          (resumo.EEF ?? 0) +
          (resumo.incompletos ?? 0),
        color: 'text-primary',
      },
      {
        label: 'Aptos',
        filtro: 'APTO',
        value: resumo.apto ?? 0,
        color: 'text-blue-600',
      },
      {
        label: 'N/Aptos',
        filtro: 'N/APTO',
        value: resumo.nao_apto ?? 0,
        color: 'text-destructive',
      },
      {
        label: 'EEF',
        filtro: 'EEF',
        value: resumo.EEF ?? 0,
        color: 'text-orange-600',
      },
      {
        label: 'Incompletos',
        filtro: 'sem_notas',
        value: resumo.incompletos ?? 0,
        color: 'text-muted-foreground',
      },
    ],

    final: [
      {
        label: 'Total',
        filtro: null,
        value: resumo.total ?? 0,
        color: 'text-primary',
      },
      {
        label: 'Transitam',
        filtro: 'transita',
        value: resumo.transita ?? 0,
        color: 'text-blue-600',
      },
      {
        label: 'N/Transitam',
        filtro: 'reprovado',
        value: resumo.reprovados ?? 0,
        color: 'text-destructive',
      },
      {
        label: 'c/ Def.',
        filtro: 'transita_com_deficiencia',
        value: resumo.transita_com_deficiencia ?? 0,
        color: 'text-amber-600',
      },
      {
        label: 'Recurso',
        filtro: 'recurso',
        value: resumo.recurso ?? 0,
        color: 'text-green-600',
      },
      {
        label: 'EEF',
        filtro: 'EEF',
        value: resumo.EEF ?? 0,
        color: 'text-orange-600',
      },
      {
        label: 'Incompletos',
        filtro: 'incompleto',
        value: resumo.incompletos ?? 0,
        color: 'text-muted-foreground',
      },
    ],

    recurso: [
      {
        label: 'Total',
        filtro: null,
        value: resumo.total ?? 0,
        color: 'text-primary',
      },
      {
        label: 'Transitam',
        filtro: 'aprovado_recurso',
        value: resumo.transita ?? 0,
        color: 'text-blue-600',
      },
      {
        label: 'N/Transitam',
        filtro: 'reprovado_recurso',
        value: resumo.nao_transita ?? 0,
        color: 'text-destructive',
      },
      {
        label: 'Incompletos',
        filtro: 'pendente',
        value: resumo.incompletos ?? 0,
        color: 'text-muted-foreground',
      },
    ],
  };

  const colsMap = {
    1: 'grid-cols-1',
    2: 'grid-cols-2',
    3: 'grid-cols-3',
    4: 'grid-cols-4',
    5: 'grid-cols-5',
    6: 'grid-cols-6',
    7: 'grid-cols-7',
  };

  const items = cards[tipo] ?? [];

  return (
    <div className={`grid ${colsMap[items.length] ?? 'grid-cols-4'}`}>
      {items.map((card) => (
        <Card
          key={card.label}
          onClick={() =>
            onFiltro(filtroActivo === card.filtro ? null : card.filtro)
          }
          className={`cursor-pointer transition-colors`}
        >
          <CardHeader className="p-4">
            <CardDescription className="text-xs">{card.label}</CardDescription>
            <CardTitle className={`text-2xl font-semibold ${card.color}`}>
              {card.value}
            </CardTitle>
          </CardHeader>
        </Card>
      ))}
    </div>
  );
}
