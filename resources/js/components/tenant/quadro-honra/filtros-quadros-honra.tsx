import { router } from '@inertiajs/react';
import { index as quadroHonraIndex } from '@/actions/App/Http/Controllers/Tenant/QuadroHonraController';
import { Card, CardContent } from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import type { FiltroQuadrosHonra } from '@/types/quadro-honra';

type FiltrosQuadrosHonraProps = {
  filtros: FiltroQuadrosHonra;
};

/** Permite seleccionar o trimestre do Quadro de Honra. */
export function FiltrosQuadrosHonra({ filtros }: FiltrosQuadrosHonraProps) {
  const aplicarFiltros = (alteracoes: Partial<FiltroQuadrosHonra>) => {
    const novosFiltros = { ...filtros, ...alteracoes };
    const query: Record<string, string> = {};

    if (novosFiltros.periodo) {
      query.periodo = novosFiltros.periodo;
    }

    router.get(quadroHonraIndex().url, query, {
      preserveScroll: true,
      preserveState: true,
      replace: true,
    });
  };

  return (
    <Card>
      <CardContent className="flex flex-col gap-3 pt-4">
        <Select
          value={filtros.periodo}
          onValueChange={(periodo) =>
            aplicarFiltros({
              periodo: periodo as FiltroQuadrosHonra['periodo'],
            })
          }
        >
          <SelectTrigger
            className="w-full sm:max-w-xs"
            aria-label="Selecionar trimestre"
          >
            <SelectValue placeholder="Selecionar trimestre" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="1">1.º trimestre</SelectItem>
            <SelectItem value="2">2.º trimestre</SelectItem>
            <SelectItem value="3">3.º trimestre</SelectItem>
          </SelectContent>
        </Select>
      </CardContent>
    </Card>
  );
}
