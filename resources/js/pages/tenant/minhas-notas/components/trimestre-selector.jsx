import {
  Select,
  SelectContent,
  SelectItem,
  SelectSeparator,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

/**
 * Selector para escolher o trimestre a visualizar
 *
 * @component
 * @param {string} value - Período selecionado
 * @param {function} onchange - Callback ao mudar trimestre
 * @returns {JSX.Element}
 */
export function TrimestroSelector({ value, onChange }) {
  return (
    <div className="flex w-full flex-col gap-2 md:w-fit">
      <Select value={String(value)} onValueChange={onChange}>
        <SelectTrigger className="w-full md:w-fit">
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="1">1º Trimestre</SelectItem>
          <SelectItem value="2">2º Trimestre</SelectItem>
          <SelectItem value="3">3º Trimestre</SelectItem>
          <SelectItem value="final">Pauta Final</SelectItem>
          <SelectItem value="recurso">Recurso</SelectItem>
        </SelectContent>
      </Select>
    </div>
  );
}
