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

export default function DisciplinaForm({
  disciplinas,
  disciplinaIds,
  setDisciplinaIds,
  errors,
  processing,
}) {
  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md lg:max-w-195">
      <Card className="overflow-visible">
        <CardHeader className="border-b">
          <CardTitle>Associar Disciplinas</CardTitle>
        </CardHeader>

        <CardContent>
          <FieldGroup>
            <FieldSet>
              <Field>
                <FieldLabel>Disciplinas</FieldLabel>

                <MultipleSelect
                  placeholder="Selecione as disciplinas"
                  items={disciplinas.map((d) => ({
                    value: d.id,
                    label: d.nome,
                  }))}
                  onChange={(opts) =>
                    setDisciplinaIds(opts.map((o) => o.value))
                  }
                  value={disciplinaIds.map((id) => ({
                    value: id,
                    label: disciplinas.find((d) => d.id === id)?.nome ?? id,
                  }))}
                />

                {errors.disciplina_ids && (
                  <FieldError>{errors.disciplina_ids}</FieldError>
                )}
              </Field>

              <Field>
                <Button type="submit" disabled={processing}>
                  {processing ? (
                    <Loader2 className="animate-spin" />
                  ) : (
                    'Associar'
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
