import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { Loader2, Plus, Trash2 } from 'lucide-react';
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
  readOnly = false,
}) {
  const {
    horarios,
    toggle,
    addSlot,
    removeSlot,
    update,
    algumAtivo,
    valido,
    erros,
    getPayload,
    DIAS_SEMANA,
  } = useHorarios(defaultValues);

  const handleSubmit = (e) => {
    e.preventDefault();
    if (readOnly || !valido) {
      return;
    }

    onSubmit(getPayload());
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <Table className="border">
        <TableHeader>
          <TableRow className="bg-muted/72">
            <TableHead className="w-40 px-4">Dia</TableHead>
            <TableHead>Tempos</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {DIAS_SEMANA.map(({ id, nome }) => {
            const slots = horarios[id];
            const ativo = slots.length > 0;

            return (
              <TableRow key={id}>
                <TableCell className="px-4 align-top">
                  <label className="flex cursor-pointer items-center gap-2 py-2">
                    <Checkbox
                      checked={ativo}
                      disabled={readOnly}
                      onCheckedChange={() => toggle(id)}
                    />
                    <span className={ativo ? 'font-medium' : 'opacity-40'}>
                      {nome}
                    </span>
                  </label>
                </TableCell>

                <TableCell className="py-2">
                  <div className="flex flex-col gap-2">
                    {slots.map((s, i) => (
                      <div key={i} className="flex items-center gap-2">
                        <Input
                          type="time"
                          value={s.hora_inicio}
                          disabled={readOnly}
                          onChange={(e) =>
                            update(id, i, 'hora_inicio', e.target.value)
                          }
                          className="w-28"
                        />
                        <span className="text-muted-foreground">–</span>
                        <Input
                          type="time"
                          value={s.hora_fim}
                          disabled={readOnly}
                          onChange={(e) =>
                            update(id, i, 'hora_fim', e.target.value)
                          }
                          className="w-28"
                        />

                        {!readOnly && (
                          <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            aria-label="Remover tempo"
                            className="hover:cursor-pointer"
                            onClick={() => removeSlot(id, i)}
                          >
                            <Trash2 className="size-4" />
                          </Button>
                        )}
                      </div>
                    ))}

                    {ativo && !readOnly && (
                      <Button
                        type="button"
                        variant="outline"
                        size="xs"
                        className="w-fit hover:cursor-pointer"
                        onClick={() => addSlot(id)}
                      >
                        <Plus className="size-3" />
                        Adicionar tempo
                      </Button>
                    )}

                    {erros[id] && (
                      <p className="text-xs text-destructive">{erros[id]}</p>
                    )}
                  </div>
                </TableCell>
              </TableRow>
            );
          })}
        </TableBody>
      </Table>

      {!readOnly && !algumAtivo && (
        <p className="text-destructive">Selecione pelo menos um dia.</p>
      )}

      {!readOnly && (
        <Button
          type="submit"
          disabled={isLoading || !valido}
          className="w-full"
        >
          {isLoading && <Loader2 className="mr-2 size-4 animate-spin" />}
          {isLoading ? 'A definir...' : 'Definir Horários'}
        </Button>
      )}
    </form>
  );
}
