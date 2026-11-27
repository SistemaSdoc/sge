import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import { FieldError } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export function DialogoDataDefesa({
  open,
  onOpenChange,
  data,
  setData,
  errors,
  processing,
  onSubmit,
  onCancel,
}) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Definir data da defesa</DialogTitle>
        </DialogHeader>

        <div className="space-y-4 py-2">
          <div className="space-y-1.5">
            <Label htmlFor="data_defesa">Data da defesa</Label>
            <Input
              id="data_defesa"
              type="date"
              value={data.data_defesa}
              onChange={(e) => setData('data_defesa', e.target.value)}
            />
            {errors.data_defesa && (
              <FieldError>{errors.data_defesa}</FieldError>
            )}
          </div>

          <div className="space-y-1.5">
            <Label htmlFor="hora_defesa">Hora da defesa</Label>
            <Input
              id="hora_defesa"
              type="time"
              value={data.hora_defesa}
              onChange={(e) => setData('hora_defesa', e.target.value)}
            />
            {errors.hora_defesa && (
              <FieldError>{errors.hora_defesa}</FieldError>
            )}
          </div>

          <div className="space-y-1.5">
            <Label htmlFor="local_defesa">Local da defesa</Label>
            <Input
              id="local_defesa"
              type="text"
              placeholder="Ex: Sala 12, Bloco A"
              value={data.local_defesa}
              onChange={(e) => setData('local_defesa', e.target.value)}
            />
            {errors.local_defesa && (
              <FieldError>{errors.local_defesa}</FieldError>
            )}
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={onCancel}>
            Cancelar
          </Button>
          <Button onClick={onSubmit} disabled={processing}>
            {processing ? 'A definir...' : 'Definir data'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
