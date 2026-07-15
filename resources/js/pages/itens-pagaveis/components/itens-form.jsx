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
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';

const tipos = [
  { label: 'Mensalidade', value: 'mensalidade' },
  { label: 'Matrícula', value: 'matricula' },
  { label: 'Taxa', value: 'taxa' },
  { label: 'Outro', value: 'outro' },
];

export function ItensForm({
  title,
  submitLabel = 'Guardar',
  data,
  setData,
  errors,
  processing,
  submitFn,
}) {
  return (
    <div className="mx-auto w-full max-w-2xl p-6">
      <form onSubmit={submitFn}>
        <Card>
          <CardHeader className="border-b">
            <CardTitle>{title}</CardTitle>
          </CardHeader>

          <CardContent>
            <FieldGroup>
              <FieldSet>
                <Field data-invalid={Boolean(errors?.nome)}>
                  <FieldLabel htmlFor="nome">Nome</FieldLabel>
                  <Input
                    id="nome"
                    type="text"
                    placeholder="Nome do item (ex.: Propina mensal)"
                    value={data.nome ?? ''}
                    onChange={(e) => setData('nome', e.target.value)}
                    aria-invalid={Boolean(errors?.nome)}
                  />
                  {errors?.nome && <FieldError>{errors.nome}</FieldError>}
                </Field>

                {/*<div className="mt-4 grid gap-4 md:grid-cols-2">
                  <Field>
                    <FieldLabel>Tipo</FieldLabel>
                    <ToggleGroup
                      type="single"
                      variant="outline"
                      value={data.tipo ?? 'mensalidade'}
                      onValueChange={(val) => setData('tipo', val)}
                      className="flex-wrap"
                    >
                      {tipos.map((t) => (
                        <ToggleGroupItem key={t.value} value={t.value}>
                          {t.label}
                        </ToggleGroupItem>
                      ))}
                    </ToggleGroup>
                    {errors?.tipo && <FieldError>{errors.tipo}</FieldError>}
                  </Field>
                </div>*/}

                <Field data-invalid={Boolean(errors?.valor_padrao)}>
                  <FieldLabel htmlFor="valor_padrao">Valor padrão</FieldLabel>
                  <Input
                    id="valor_padrao"
                    type="number"
                    min="0"
                    step="0.01"
                    placeholder="0,00"
                    value={data.valor_padrao ?? ''}
                    onChange={(e) => setData('valor_padrao', e.target.value)}
                    aria-invalid={Boolean(errors?.valor_padrao)}
                  />
                  {errors?.valor_padrao && (
                    <FieldError>{errors.valor_padrao}</FieldError>
                  )}
                </Field>

                <Button type="submit" disabled={processing}>
                  {processing ? 'A guardar...' : submitLabel}
                </Button>
              </FieldSet>
            </FieldGroup>
          </CardContent>
        </Card>
      </form>
    </div>
  );
}
