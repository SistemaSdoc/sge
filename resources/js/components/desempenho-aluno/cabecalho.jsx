import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

export function Cabecalho({ filtrosDisponiveis, filtrosActivos, onTrocarAno }) {
  return (
    <div className="flex flex-wrap items-start justify-between gap-4">
      <div className="flex flex-col gap-1">
        <h1 className="font-heading text-2xl leading-tight font-medium">
          Meu Desempenho
        </h1>
        <p className="text-xs text-muted-foreground">
          Acompanhe a evolução do seu rendimento ao longo do ano lectivo
        </p>
      </div>

      <div className="flex items-center gap-2">
        <span className="text-xs text-muted-foreground">Ano lectivo</span>
        <Select
          value={String(filtrosActivos.ano_lectivo_id ?? '')}
          onValueChange={onTrocarAno}
        >
          <SelectTrigger size="sm" className="min-w-40">
            <SelectValue placeholder="Ano" />
          </SelectTrigger>
          <SelectContent>
            {filtrosDisponiveis.anosLectivos.map((a) => (
              <SelectItem key={a.id} value={String(a.id)}>
                {a.ano}
                {a.activo ? ' (actual)' : ''}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>
    </div>
  );
}
