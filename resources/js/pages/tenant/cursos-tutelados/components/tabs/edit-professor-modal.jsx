import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
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
import { Switch } from '@/components/ui/switch';
import { update } from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoProfessorController';
import { Spinner } from '@/components/spinner';

export default function EditProfessorForm({ vinculo, onClose, params }) {
  const [tipo, setTipo] = useState(vinculo?.tipo ?? '');
  const [coordenador, setCoordenador] = useState(!!vinculo?.coordenador ?? false);
  const [opap, setOpap] = useState(!!vinculo?.opap ?? false);
  const [errors, setErrors] = useState({});
  const [processing, setProcessing] = useState(false);

  function handleSubmit() {
    setProcessing(true);
    router.put(
      update({
        ...params,
        professor: vinculo.vinculo_id,
      }).url,
      { tipo, coordenador, opap },
      {
        preserveScroll: true,
        onSuccess: () => onClose?.(),
        onError: (e) => setErrors(e),
        onFinish: () => setProcessing(false),
      },
    );
  }

  return (
    <FieldGroup>
      <FieldSet>
        <Field>
          <FieldLabel>Professor</FieldLabel>
          <p className="text-sm text-muted-foreground">{vinculo?.nome}</p>
        </Field>

        <div className="flex items-center gap-4">
          <Field orientation="horizontal" className="flex w-fit">
            <FieldLabel htmlFor="coordenador">Coordenador</FieldLabel>
            <Switch
              size="sm"
              id="coordenador"
              checked={coordenador}
              onCheckedChange={setCoordenador}
            />
          </Field>

          <Field orientation="horizontal" className="flex w-fit">
            <FieldLabel htmlFor="opap">OPAP</FieldLabel>
            <Switch
              size="sm"
              id="opap"
              checked={opap}
              onCheckedChange={setOpap}
            />
          </Field>
        </div>

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
                <Spinner className="animate-spin" /> A guardar...
              </>
            ) : (
              'Guardar'
            )}
          </Button>
        </Field>
      </FieldSet>
    </FieldGroup>
  );
}