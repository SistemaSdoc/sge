import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

const TODOS = 'todos';

function CampoSelect({ value, onChange, todosLabel, className, children }) {
  return (
    <Select value={value} onValueChange={(v) => onChange(v === TODOS ? '' : v)}>
      <SelectTrigger className={className}>
        <SelectValue />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value={TODOS}>{todosLabel}</SelectItem>
        {children}
      </SelectContent>
    </Select>
  );
}

export default function FiltrosDesempenho({ disponiveis, activos, onChange }) {
  return (
    <>
      <CampoSelect
        className="w-36"
        todosLabel="Todos os anos"
        value={activos.ano_lectivo_id ? String(activos.ano_lectivo_id) : TODOS}
        onChange={(v) => onChange({ ano_lectivo_id: v })}
      >
        {disponiveis.anosLectivos.map((a) => (
          <SelectItem key={a.id} value={String(a.id)}>
            {a.ano}
          </SelectItem>
        ))}
      </CampoSelect>

      <CampoSelect
        className="w-40"
        todosLabel="Todos os trimestres"
        value={activos.periodo ? String(activos.periodo) : TODOS}
        onChange={(v) => onChange({ periodo: v })}
      >
        {disponiveis.periodos.map((p) => (
          <SelectItem key={p} value={String(p)}>
            {p}º Trimestre
          </SelectItem>
        ))}
      </CampoSelect>
    </>
  );
}
