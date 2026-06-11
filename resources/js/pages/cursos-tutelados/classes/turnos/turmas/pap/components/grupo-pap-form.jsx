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
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import MultipleSelect from '@/components/multiple-select';

export default function GrupoPapForm({
  errors,
  processing,
  professores = [],
  alunos = [],
  professorTutorId,
  setProfessorTutorId,
  alunoIds,
  setAlunoIds,
  grupoPap,
}) {
  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md lg:max-w-195">
      <Card className="overflow-visible">
        <CardHeader className="border-b">
          <CardTitle>Criar grupo PAP</CardTitle>
        </CardHeader>

        <CardContent>
          <FieldGroup>
            <FieldSet>
              <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Field>
                  <FieldLabel>Nome do grupo</FieldLabel>
                  <Input
                    name="nome_grupo"
                    disabled={processing}
                    placeholder="Ex.: Grupo Alpha"
                    defaultValue={grupoPap?.nome_grupo ?? ''}
                  />
                  {errors.nome_grupo && (
                    <FieldError>{errors.nome_grupo}</FieldError>
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
              </div>

              <Field>
                <FieldLabel>Professor tutor</FieldLabel>
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
                          {p.user.nome}
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
                {errors.alunos && <FieldError>{errors.alunos}</FieldError>}
              </Field>

              <Field>
                <FieldLabel>Estudo de caso</FieldLabel>
                <Textarea
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
