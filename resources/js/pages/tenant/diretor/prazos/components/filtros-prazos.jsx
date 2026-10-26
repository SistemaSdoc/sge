import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

export default function FiltrosPrazos({
  filtros,
  disciplinas,
  onChange,
  onLimpar,
}) {
  const handleChange = (field, value) => {
    onChange({ ...filtros, [field]: value });
  };

  const temFiltrosAtivos =
    (filtros.status && filtros.status !== 'todos') ||
    (filtros.disciplina_id && filtros.disciplina_id !== 'todas');

  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between">
        <h2 className="text-sm font-semibold whitespace-nowrap">Filtros</h2>
        {temFiltrosAtivos && (
          <Button
            variant="outline"
            size="sm"
            onClick={onLimpar}
            className="hover:cursor-pointer"
          >
            Limpar
          </Button>
        )}
      </div>

      <div className="flex flex-wrap items-center gap-2">
        <Select
          value={filtros.status || 'todos'}
          onValueChange={(value) => handleChange('status', value)}
        >
          <SelectTrigger className="w-full sm:w-44">
            <SelectValue placeholder="Status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="todos">Todos os estados</SelectItem>
            <SelectItem value="aberto">Aberto</SelectItem>
            <SelectItem value="fechado">Fechado</SelectItem>
            <SelectItem value="expirado">Expirado</SelectItem>
          </SelectContent>
        </Select>

        <Select
          value={filtros.disciplina_id || 'todas'}
          onValueChange={(value) => handleChange('disciplina_id', value)}
        >
          <SelectTrigger className="w-full sm:w-56">
            <SelectValue placeholder="Disciplina" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="todas">Todas as disciplinas</SelectItem>
            {disciplinas?.map((d) => (
              <SelectItem key={d.id} value={d.id}>
                {d.nome}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>
    </div>
  );
}