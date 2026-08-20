import { Button } from '@/components/ui/button';
import { Loader2 } from 'lucide-react';
import { Field, FieldLabel, FieldError } from '@/components/ui/field';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { useState } from 'react';

export function AtribuirTurmaForm({
  turmas,
  data,
  setData,
  errors,
  processing,
  submitFn,
}) {
  return (
    <form onSubmit={submitFn} className="space-y-4">
      <Field data-invalid={!!errors?.turma_id}>
        <FieldLabel htmlFor="turma-select">Turma</FieldLabel>
        <Select
          value={data.turma_id}
          onValueChange={(value) => setData({ ...data, turma_id: value })}
          disabled={processing}
        >
          <SelectTrigger className="w-full">
            <SelectValue placeholder="Selecione a turma" />
          </SelectTrigger>
          <SelectContent>
            <SelectGroup>
              <SelectLabel>Turmas disponíveis</SelectLabel>
              {turmas?.map((t) => (
                <SelectItem key={t.id} value={String(t.id)}>
                  {t.nome} — {t.classe}
                </SelectItem>
              ))}
            </SelectGroup>
          </SelectContent>
        </Select>
        {errors?.turma_id && <FieldError>{errors?.turma_id}</FieldError>}
      </Field>

      <Button
        type="submit"
        disabled={processing || !data.turma_id}
        className="w-full"
      >
        {processing && <Loader2 className="mr-2 size-4 animate-spin" />}
        {processing ? 'A atribuir...' : 'Atribuir Turma'}
      </Button>
    </form>
  );
}
