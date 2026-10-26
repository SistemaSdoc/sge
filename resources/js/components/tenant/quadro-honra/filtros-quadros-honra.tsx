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
import type {
  AnoLectivoOption,
  FiltroQuadrosHonra,
} from '@/types/quadro-honra';

type FiltrosQuadrosHonraProps = {
  anosLectivos: AnoLectivoOption[];
  filtros: FiltroQuadrosHonra;
};

/** Filtra o histórico por ano lectivo e tipo, mantendo a navegação Inertia. */
export function FiltrosQuadrosHonra({
  anosLectivos,
  filtros,
}: FiltrosQuadrosHonraProps) {
  const aplicarFiltros = (alteracoes: Partial<FiltroQuadrosHonra>) => {
    const novosFiltros = { ...filtros, ...alteracoes };
    const query: Record<string, string> = {};

    if (novosFiltros.ano_lectivo_id) {
      query.ano_lectivo_id = novosFiltros.ano_lectivo_id;
    }

    if (novosFiltros.tipo) {
      query.tipo = novosFiltros.tipo;
    }

    router.get(quadroHonraIndex().url, query, {
      preserveScroll: true,
      preserveState: true,
      replace: true,
    });
  };

  return (
    <Card>
      <CardContent className="flex flex-col gap-3 pt-4 sm:flex-row">
        <Select
          value={filtros.ano_lectivo_id || 'todos'}
          onValueChange={(ano) =>
            aplicarFiltros({ ano_lectivo_id: ano === 'todos' ? '' : ano })
          }
        >
          <SelectTrigger
            className="w-full sm:max-w-xs"
            aria-label="Filtrar por ano lectivo"
          >
            <SelectValue placeholder="Todos os anos lectivos" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="todos">Todos os anos lectivos</SelectItem>
            {anosLectivos.map((ano) => (
              <SelectItem key={ano.id} value={ano.id}>
                {ano.nome}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        <Select
          value={filtros.tipo || 'todos'}
          onValueChange={(tipo) =>
            aplicarFiltros({
              tipo:
                tipo === 'todos' ? '' : (tipo as FiltroQuadrosHonra['tipo']),
            })
          }
        >
          <SelectTrigger
            className="w-full sm:max-w-xs"
            aria-label="Filtrar por tipo de quadro"
          >
            <SelectValue placeholder="Todos os tipos" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="todos">Todos os tipos</SelectItem>
            <SelectItem value="geral">Quadro geral</SelectItem>
            <SelectItem value="curso">Quadro por curso</SelectItem>
          </SelectContent>
        </Select>
      </CardContent>
    </Card>
  );
}
