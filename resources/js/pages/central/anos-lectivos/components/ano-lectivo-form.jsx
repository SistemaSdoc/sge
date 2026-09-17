import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldSet,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { ArrowUpLeft } from 'lucide-react';

export default function AnoLectivoForm({
  title,
  description,
  data,
  setData,
  errors,
  processing,
  onSubmit,
}) {
  return (
    <div className="mx-auto w-full max-w-2xl px-6 py-6">
      <form onSubmit={onSubmit}>
        <Card>
          <CardHeader className="border-b">
            <CardTitle>{title}</CardTitle>
            <CardDescription>{description}</CardDescription>
          </CardHeader>

          <CardContent>
            <FieldGroup>
              <FieldSet>
                <Field>
                  <FieldLabel htmlFor="ano_inicio">Ano de início</FieldLabel>
                  <Input
                    id="ano_inicio"
                    type="number"
                    min="2000"
                    max="2200"
                    value={data.ano_inicio}
                    onChange={(event) =>
                      setData('ano_inicio', event.target.value)
                    }
                    disabled={title.startsWith('Editar')}
                  />
                  {errors.ano_inicio && (
                    <FieldError>{errors.ano_inicio}</FieldError>
                  )}
                </Field>

                <Field>
                  <Button type="submit" disabled={processing}>
                    Guardar ano lectivo
                  </Button>

                  <Button
                    type="button"
                    variant="outline"
                    disabled={processing}
                    onClick={() => window.history.back()}
                  >
                    <ArrowUpLeft />
                    Voltar
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
