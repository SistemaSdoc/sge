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

export function CursoForm({
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
                    placeholder="Ex.: Informática de gestão"
                    value={data.nome}
                    onChange={(e) => setData('nome', e.target.value)}
                  />
                  {errors.nome && <FieldError>{errors.nome}</FieldError>}
                </Field>

                <Field>
                  <FieldLabel htmlFor="duracao_anos">Duração (anos)</FieldLabel>
                  <Input
                    id="duracao_anos"
                    type="number"
                    placeholder="Ex.: 3"
                    value={data.duracao_anos}
                    onChange={(e) => setData('duracao_anos', e.target.value)}
                  />
                  {errors.duracao_anos && (
                    <FieldError>{errors.duracao_anos}</FieldError>
                  )}
                </Field>

                <Field>
                  <FieldLabel htmlFor="descricao">Descrição</FieldLabel>
                  <Textarea
                    id="descricao"
                    placeholder="..."
                    value={data.descricao}
                    onChange={(e) => setData('descricao', e.target.value)}
                  />
                  {errors.descricao && (
                    <FieldError>{errors.descricao}</FieldError>
                  )}
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
