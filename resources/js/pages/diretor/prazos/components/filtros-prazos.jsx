import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

export default function FiltrosPrazos({ filtros, disciplinas, onChange, onLimpar }) {
  const handleChange = (field, value) => {
    onChange({ ...filtros, [field]: value });
  };

  return (
    <div className="bg-card border border-border p-4 shadow-sm">
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {/* Status */}
        <div className="space-y-1.5">
          <Label htmlFor="status" className="text-sm font-medium text-muted-foreground">
            Status
          </Label>
          <Select value={filtros.status} onValueChange={(value) => handleChange('status', value)}>
            <SelectTrigger id="status" className="w-full">
              <SelectValue placeholder="Todos" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value=" ">Todos</SelectItem>
              <SelectItem value="aberto">Aberto</SelectItem>
              <SelectItem value="fechado">Fechado</SelectItem>
              <SelectItem value="expirado">Expirado</SelectItem>
            </SelectContent>
          </Select>
        </div>

        {/* Disciplina */}
        <div className="space-y-1.5">
          <Label htmlFor="disciplina" className="text-sm font-medium text-muted-foreground">
            Disciplina
          </Label>
          <Select value={filtros.disciplina_id} onValueChange={(value) => handleChange('disciplina_id', value)}>
            <SelectTrigger id="disciplina" className="w-full">
              <SelectValue placeholder="Todas" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value=" ">Todas</SelectItem>
              {disciplinas?.map((d) => (
                <SelectItem key={d.id} value={d.id}>
                  {d.nome}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        {/*  Botões ocupam 2 colunas em desktop */}
        <div className="flex flex-col sm:flex-row items-end gap-2 sm:col-span-2 lg:col-span-2 lg:justify-end">
          <Button
            variant="default"
            onClick={() => onChange(filtros)}
            className="w-full sm:w-auto"
          >
            Filtrar
          </Button>
          <Button
            variant="outline"
            onClick={onLimpar}
            className="w-full sm:w-auto"
          >
            Limpar
          </Button>
        </div>
      </div>
    </div>
  );
}