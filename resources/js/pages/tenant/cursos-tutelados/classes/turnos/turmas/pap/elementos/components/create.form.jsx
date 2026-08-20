import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldSet,
} from '@/components/ui/field';
import MultipleSelect from '@/components/multiple-select';

export function CreateForm({
  errors,
  processing,
  alunos = [],
  alunoIds,
  setAlunoIds,
}) {
  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md lg:max-w-195">
      <Card className="overflow-visible">
        <CardHeader className="border-b">
          <CardTitle>Adicionar elementos ao grupo PAP</CardTitle>
        </CardHeader>

        <CardContent>
          <FieldGroup>
            <FieldSet>
              <Field>
                <FieldLabel>Alunos</FieldLabel>

                <MultipleSelect
                  placeholder="Selecione os alunos"
                  items={alunos.map((a) => ({
                    value: a.id,
                    label: a.nome,
                  }))}
                  onChange={(opts) => setAlunoIds(opts.map((o) => o.value))}
                  value={alunoIds.map((id) => ({
                    value: id,
                    label: alunos.find((a) => a.id === id)?.nome ?? id,
                  }))}
                />

                {Object.keys(errors)
                  .filter((key) => key.startsWith('alunos'))
                  .map((key) => (
                    <FieldError key={key}>{errors[key]}</FieldError>
                  ))}
              </Field>

              <Field>
                <Button type="submit" disabled={processing}>
                  {processing ? (
                    <>
                      <Loader2 className="animate-spin" /> A adicionar...
                    </>
                  ) : (
                    <>Adicionar</>
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
