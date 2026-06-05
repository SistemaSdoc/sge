import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldSet,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

export function ClasseForm({
  title,
  submitLabel = 'Guardar',
  data,
  setData,
  errors,
  processing,
  submitFn,
}) {
  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md lg:max-w-2xl">
      <form onSubmit={submitFn}>
        <Card>
          <CardHeader className="border-b">
            <CardTitle>{title}</CardTitle>
          </CardHeader>
          <CardContent>
            <FieldGroup>
              <FieldSet>
                <Field>
                  <FieldLabel htmlFor="nome">Nome</FieldLabel>
                  <Input
                    id="nome"
                    type="text"
                    placeholder="Ex.: 10ª"
                    value={data.nome}
                    onChange={(e) => setData('nome', e.target.value)}
                  />
                  {errors.nome && <FieldError>{errors.nome}</FieldError>}
                </Field>

                <Field>
                  <FieldLabel htmlFor="ordem">Ordem</FieldLabel>
                  <Input
                    id="ordem"
                    type="number"
                    placeholder="Ex.: 1"
                    value={data.ordem}
                    onChange={(e) => setData('ordem', e.target.value)}
                  />
                  {errors.ordem && <FieldError>{errors.ordem}</FieldError>}
                </Field>

                <Field>
                  <Button type="submit" disabled={processing}>
                    {processing ? 'A guardar...' : submitLabel}
                  </Button>
                </Field>
              </FieldSet>
            </FieldGroup>
          </CardContent>
        </Card>
      </form>
    </div>
  );
}
