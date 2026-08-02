import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Loader2 } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

export default function AlunoForm({
  errors,
  processing,
  turmas = [],
  turmaId,
  setTurmaId,
  defaultValues = {},
}) {
  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md lg:max-w-195">
      <Card className="overflow-visible">
        <CardHeader className="border-b">
          <CardTitle>Editar aluno</CardTitle>
        </CardHeader>

        <CardContent>
          <FieldGroup>
            <FieldSet>
              <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Field>
                  <FieldLabel>Nome completo</FieldLabel>
                  <Input
                    name="nome"
                    disabled={processing}
                    placeholder="Ex.: João Silva"
                    defaultValue={defaultValues.nome}
                  />
                  {errors.nome && <FieldError>{errors.nome}</FieldError>}
                </Field>

                <Field>
                  <FieldLabel>Nº Bilhete</FieldLabel>
                  <Input
                    name="bi"
                    disabled={processing}
                    placeholder="Ex.: 020419607LA096"
                    defaultValue={defaultValues.bi}
                  />
                  {errors.bi && <FieldError>{errors.bi}</FieldError>}
                </Field>
              </div>

              <Field>
                <FieldLabel>Matrícula</FieldLabel>
                <Input
                  name="matricula"
                  disabled={processing}
                  placeholder="Ex.: MAT-2026-0001"
                  defaultValue={defaultValues.matricula}
                />
                {errors.matricula && (
                  <FieldError>{errors.matricula}</FieldError>
                )}
              </Field>

              <Field>
                <FieldLabel>Turma</FieldLabel>
                <Select
                  value={turmaId}
                  onValueChange={setTurmaId}
                  disabled={processing}
                >
                  <SelectTrigger className="w-full">
                    <SelectValue placeholder="Selecione a turma" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      <SelectLabel>Turmas disponíveis</SelectLabel>
                      {turmas.map((t) => (
                        <SelectItem key={t.id} value={String(t.id)}>
                          {t.nome} — {t.classe}
                        </SelectItem>
                      ))}
                    </SelectGroup>
                  </SelectContent>
                </Select>
                {errors.turma_id && <FieldError>{errors.turma_id}</FieldError>}
              </Field>

              <Field>
                <Button type="submit" disabled={processing}>
                  {processing ? (
                    <>
                      <Loader2 className="animate-spin" /> A guardar...
                    </>
                  ) : (
                    <>Guardar</>
                  )}
                </Button>
              </Field>
            </FieldSet>
          </FieldGroup>
        </CardContent>
      </Card>
    </div>
  );
}
