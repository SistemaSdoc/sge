import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { Loader2 } from 'lucide-react';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { useHorarios } from '../../hooks/use-horarios';

export function HorariosForm({
  onSubmit,
  isLoading = false,
  defaultValues = null,
}) {
  const { horarios, toggle, update, algumAtivo, getPayload, DIAS_SEMANA } =
    useHorarios(defaultValues);

  const handleSubmit = (e) => {
    e.preventDefault();
    onSubmit(getPayload());
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <Table className="border">
        <TableHeader>
          <TableRow className="bg-muted/72">
            <TableHead className="px-4">Dia</TableHead>
            <TableHead>Início</TableHead>
            <TableHead>Fim</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {DIAS_SEMANA.map(({ id, nome }) => {
            const h = horarios[id];
            return (
              <TableRow key={id} className={!h.ativo ? 'opacity-40' : ''}>
                <TableCell className="px-4">
                  <label className="flex cursor-pointer items-center gap-2">
                    <Checkbox
                      checked={h.ativo}
                      onCheckedChange={() => toggle(id)}
                    />
                    <span className={h.ativo ? 'font-medium' : ''}>{nome}</span>
                  </label>
                </TableCell>
                <TableCell>
                  <Input
                    type="time"
                    value={h.hora_inicio}
                    disabled={!h.ativo}
                    onChange={(e) => update(id, 'hora_inicio', e.target.value)}
                    className="w-28"
                  />
                </TableCell>
                <TableCell>
                  <Input
                    type="time"
                    value={h.hora_fim}
                    disabled={!h.ativo}
                    onChange={(e) => update(id, 'hora_fim', e.target.value)}
                    className="w-28"
                  />
                </TableCell>
              </TableRow>
            );
          })}
        </TableBody>
      </Table>

      {!algumAtivo && (
        <p className="text-destructive">Selecione pelo menos um dia.</p>
      )}

      <Button
        type="submit"
        disabled={isLoading || !algumAtivo}
        className="w-full"
      >
        {isLoading && <Loader2 className="mr-2 size-4 animate-spin" />}
        {isLoading ? 'A definrir.' : 'Definir Horários'}
      </Button>
    </form>
  );
}
