import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldSet,
} from '@/components/ui/field';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { update } from '@/actions/App/Http/Controllers/CursoTuteladoProfessorController';

export default function EditProfessorModal({ vinculo, open, onClose }) {
  const [tipo, setTipo] = useState(vinculo?.tipo ?? '');
  const [errors, setErrors] = useState({});
  const [processing, setProcessing] = useState(false);

  // console.log(vinculo.instituicaoId, vinculo.cursoTuteladoId, vinculo.id)
  // console.log(vinculo.vinculo_id)

  function handleSubmit() {
    setProcessing(true);
    router.put(
      update({
        instituicao: vinculo.instituicaoId,
        cursoTutelado: vinculo.cursoTuteladoId,
        professore: vinculo.vinculo_id,
      }).url,
      { tipo },
      {
        preserveScroll: true,
        onSuccess: () => onClose(),
        onError: (e) => setErrors(e),
        onFinish: () => setProcessing(false),
      },
    );
  }

  return (
    <Dialog open={open} onOpenChange={onClose}>
        <DialogContent aria-describedby={undefined}>
        <DialogHeader>
          <DialogTitle>Editar Tipo de Professor</DialogTitle>
        </DialogHeader>

        <FieldGroup>
          <FieldSet>
            <Field>
              <FieldLabel>Professor</FieldLabel>
              <p className="text-sm text-muted-foreground">{vinculo?.nome}</p>
            </Field>

            <Field>
              <FieldLabel>Tipo</FieldLabel>
              <Select value={tipo} onValueChange={setTipo}>
                <SelectTrigger className="w-full">
                  <SelectValue placeholder="Selecione o tipo" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectLabel>Tipos</SelectLabel>
                    <SelectItem value="principal">Principal</SelectItem>
                    <SelectItem value="colaborador">Colaborador</SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
              {errors?.tipo && <FieldError>{errors.tipo}</FieldError>}
            </Field>

            <Field>
              <Button onClick={handleSubmit} disabled={processing}>
                {processing ? (
                  <>
                    <Loader2 className="animate-spin" /> A guardar...
                  </>
                ) : (
                  'Guardar'
                )}
              </Button>
            </Field>
          </FieldSet>
        </FieldGroup>
      </DialogContent>
    </Dialog>
  );
}
