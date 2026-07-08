import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

/**
 * Selector para escolher o trimestre a visualizar
 *
 * @component
 * @param {number} value - Trimestre selecionado (1, 2 ou 3)
 * @param {function} onchange - Callback ao mudar trimestre
 * @returns {JSX.Element}
 */
export function TrimestroSelector({ value, onChange }) {
  return (
    <div className="flex flex-col gap-2">
      <Select value={String(value)} onValueChange={(v) => onChange(Number(v))}>
        <SelectTrigger className="w-full max-w-">
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="1">1º Trimestre</SelectItem>
          <SelectItem value="2">2º Trimestre</SelectItem>
          <SelectItem value="3">3º Trimestre</SelectItem>
        </SelectContent>
      </Select>
    </div>
  );
}
