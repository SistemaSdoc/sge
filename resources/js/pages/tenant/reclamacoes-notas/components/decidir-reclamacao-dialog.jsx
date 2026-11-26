import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

export function DecidirReclamacaoDialog({
  open,
  reclamacao,
  submitUrl,
  onClose,
}) {
  const form = useForm({
    decisao: 'em_analise',
    nota_corrigida: reclamacao?.nota_original ?? '',
    parecer: '',
  });

  const enviar = () => {
    form.patch(submitUrl, {
      preserveScroll: true,
      onSuccess: () => {
        form.reset();
        onClose();
      },
    });
  };

  return (
    <Dialog
      open={open}
      onOpenChange={(isOpen) => {
        if (!isOpen) {
          form.reset();
          onClose();
        }
      }}
    >
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Analisar reclamação</DialogTitle>
          <DialogDescription>
            Registe o estado, o parecer e, se aplicável, a nota corrigida.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4 py-2">
          <div className="space-y-1.5">
            <Label htmlFor="decisao-reclamacao">Decisão</Label>
            <Select
              value={form.data.decisao}
              onValueChange={(decisao) => form.setData('decisao', decisao)}
            >
              <SelectTrigger id="decisao-reclamacao" className="w-full">
                <SelectValue placeholder="Selecione o estado da análise" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="em_analise">Em análise</SelectItem>
                <SelectItem value="aguarda_informacao">
                  Aguardar informação
                </SelectItem>
                <SelectItem value="aprovada">
                  Aprovada - corrigir nota
                </SelectItem>
                <SelectItem value="reprovada">
                  Reprovada - manter nota
                </SelectItem>
              </SelectContent>
            </Select>
            {form.errors.decisao && (
              <p className="text-sm text-destructive">{form.errors.decisao}</p>
            )}
          </div>

          {form.data.decisao === 'aprovada' && (
            <div className="space-y-1.5">
              <Label htmlFor="nota-corrigida">Nota corrigida</Label>
              <Input
                id="nota-corrigida"
                type="number"
                min="0"
                max="20"
                step="0.1"
                value={form.data.nota_corrigida}
                onChange={(event) =>
                  form.setData('nota_corrigida', event.target.value)
                }
              />
              {form.errors.nota_corrigida && (
                <p className="text-sm text-destructive">
                  {form.errors.nota_corrigida}
                </p>
              )}
            </div>
          )}

          <div className="space-y-1.5">
            <Label htmlFor="parecer-reclamacao">Parecer</Label>
            <Textarea
              id="parecer-reclamacao"
              rows={4}
              value={form.data.parecer}
              onChange={(event) => form.setData('parecer', event.target.value)}
            />
            {form.errors.parecer && (
              <p className="text-sm text-destructive">{form.errors.parecer}</p>
            )}
          </div>
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" onClick={onClose}>
            Cancelar
          </Button>
          <Button
            type="button"
            disabled={form.processing}
            onClick={enviar}
          >
            {form.processing ? 'A guardar...' : 'Guardar decisão'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
