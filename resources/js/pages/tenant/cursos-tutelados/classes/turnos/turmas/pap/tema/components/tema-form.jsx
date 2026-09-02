import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useState } from 'react';
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldSet,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

export function TemaForm({ title, errors, processing, grupoPap, professores = [], }) {
  const [professorTutorId, setProfessorTutorId] = useState(undefined);
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
                <FieldLabel>Professor tutor</FieldLabel>
                <input type="hidden" name="professor_tutor_id" value={professorTutorId} />
                <Select
                  value={professorTutorId || undefined}
                  onValueChange={setProfessorTutorId}
                  disabled={processing}
                >
                  <SelectTrigger className="w-full">
                    <SelectValue placeholder="Selecione o professor tutor" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      <SelectLabel>Professores</SelectLabel>
                      {professores.map((p) => (
                        <SelectItem key={p.id} value={String(p.id)}>
                          {p.nome}
                        </SelectItem>
                      ))}
                    </SelectGroup>
                  </SelectContent>
                </Select>
                {errors.professor_tutor_id && (
                  <FieldError>{errors.professor_tutor_id}</FieldError>
                )}
              </Field>

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
