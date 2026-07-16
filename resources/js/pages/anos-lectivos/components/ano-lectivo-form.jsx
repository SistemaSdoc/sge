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

export function AnoLectivoForm({
  title,
  submitLabel = 'Guardar',
  data,
  setData,
  errors,
  processing,
  submitFn,
}) {
  return (
    <div className="mx-auto w-full max-w-2xl px-6 py-6">
      <form onSubmit={submitFn}>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between border-b">
            <CardTitle>{title}</CardTitle>
          </CardHeader>

          <CardContent className="">
            <FieldGroup>
              <FieldSet>
                {/* Data de Início */}
                <Field>
                  <FieldLabel htmlFor="data_inicio">Data de início</FieldLabel>
                  <Input
                    id="data_inicio"
                    type="date"
                    value={data.data_inicio ?? ''}
                    onChange={(e) => setData('data_inicio', e.target.value)}
                  />
                  {errors.data_inicio && (
                    <FieldError>{errors.data_inicio}</FieldError>
                  )}
                </Field>

                {/* Data de Fim */}
                <Field>
                  <FieldLabel htmlFor="data_fim">Data de fim</FieldLabel>
                  <Input
                    id="data_fim"
                    type="date"
                    value={data.data_fim ?? ''}
                    onChange={(e) => setData('data_fim', e.target.value)}
                  />
                  {errors.data_fim && (
                    <FieldError>{errors.data_fim}</FieldError>
                  )}
                </Field>

                {/* Submit */}
                <Button type="submit" className="w-full" disabled={processing}>
                  {processing ? 'A publicar...' : submitLabel}
                </Button>
              </FieldSet>
            </FieldGroup>
          </CardContent>
        </Card>
      </form>
    </div>
  );
}
