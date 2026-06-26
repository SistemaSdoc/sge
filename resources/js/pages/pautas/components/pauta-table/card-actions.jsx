import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { PERIODOS } from './utils';
import { Button } from '@/components/ui/button';
import { CardAction } from '@/components/ui/card';
import { exportarExcel } from '@/actions/App/Http/Controllers/ExportarPautaController';

export function PautaCardActions({ params, periodo, setPeriodo }) {
  return (
    <CardAction className="flex items-center gap-3">
      <Select value={periodo} onValueChange={setPeriodo}>
        <SelectTrigger className="w-44">
          <SelectValue placeholder="Seleccionar período" />
        </SelectTrigger>

        <SelectContent>
          {PERIODOS.map((periodoItem) => (
            <SelectItem key={periodoItem.value} value={periodoItem.value}>
              {periodoItem.label}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>

      <Button variant="outline" asChild>
        <a
          href={exportarExcel({ ...params }).url + `?periodo=${periodo}`}
          target="_blank"
          rel="noopener noreferrer"
        >
          Exportar
        </a>
      </Button>
    </CardAction>
  );
}
