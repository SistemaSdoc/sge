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
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

export function TemaForm({ title, errors, processing, grupoPap }) {
  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md lg:max-w-195">
      <Card className="overflow-visible">
        <CardHeader className="border-b">
          <CardTitle>{title}</CardTitle>
        </CardHeader>

        <CardContent>
          <FieldGroup>
            <FieldSet>
              <Field>
                <FieldLabel>Tema</FieldLabel>
                <Input
                  name="tema_grupo"
                  disabled={processing}
                  placeholder="Ex.: Sistema de Gestão Escolar"
                  defaultValue={grupoPap?.tema_grupo ?? ''}
                />
                {errors.tema_grupo && (
                  <FieldError>{errors.tema_grupo}</FieldError>
                )}
              </Field>

              <Field>
                <FieldLabel>Problema</FieldLabel>
                <Input
                  name="problema"
                  disabled={processing}
                  placeholder="Ex.: Dificuldades na gestão de alunos e professores"
                  defaultValue={grupoPap?.problema ?? ''}
                />
                {errors.problema && <FieldError>{errors.problema}</FieldError>}
              </Field>

              <Field>
                <FieldLabel>Objectivos</FieldLabel>
                <Textarea
                  name="objectivos"
                  disabled={processing}
                  placeholder="Descreve os objectivos geral e específicos..."
                  defaultValue={grupoPap?.objectivos ?? ''}
                />
                {errors.objectivos && (
                  <FieldError>{errors.objectivos}</FieldError>
                )}
              </Field>

              <Field>
                <FieldLabel>Estudo de caso</FieldLabel>
                <Input
                  name="estudo_caso"
                  disabled={processing}
                  placeholder="Descreve o estudo de caso..."
                  defaultValue={grupoPap?.estudo_caso ?? ''}
                />
                {errors.estudo_caso && (
                  <FieldError>{errors.estudo_caso}</FieldError>
                )}
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
