import { Link } from '@inertiajs/react';
import { ArrowUpLeft, Loader2 } from 'lucide-react';
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
  title,
  errors,
  processing,
  professores = [],
  alunos = [],
  nomeGrupo,
  setNomeGrupo,
  professorTutorId,
  setProfessorTutorId,
  alunoIds,
  setAlunoIds,
  grupoPap,
  turmaUrl,
}) {
  return (
    <div className="mx-auto w-full max-w-sm p-4 md:max-w-md md:p-6 lg:max-w-195">
      <Card className="overflow-visible">
        <CardHeader className="border-b">
          <CardTitle>{title}</CardTitle>
        </CardHeader>

        <CardContent>
          <FieldGroup>
            <FieldSet>
              <div className="grid grid-cols-1 gap-4 md:grid-cols-1">
                <Field>
                  <FieldLabel>Nome do grupo</FieldLabel>
                  <Input
                    name="nome_grupo"
                    disabled={processing}
                    placeholder="Ex.: Grupo Alpha"
                    value={nomeGrupo}
                    onChange={(event) => setNomeGrupo(event.target.value)}
                  />
                  {errors.nome_grupo && (
                    <FieldError>{errors.nome_grupo}</FieldError>
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

                  {Object.keys(errors)
                    .filter((key) => key.startsWith('alunos'))
                    .map((key) => (
                      <FieldError key={key}>{errors[key]}</FieldError>
                    ))}
                </Field>

                {/* <Field>
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
                            {p.nome}
                          </SelectItem>
                        ))}
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  {errors.professor_tutor_id && (
                    <FieldError>{errors.professor_tutor_id}</FieldError>
                  )}
                </Field>*/}
              </div>

              <Field>
                <Button
                  type="submit"
                  disabled={processing}
                  className="hover:cursor-pointer"
                >
                  {processing ? (
                    <>
                      <Loader2 className="animate-spin" /> A guardar...
                    </>
                  ) : (
                    <>Guardar</>
                  )}
                </Button>
                <Button asChild variant="outline">
                  <Link href={turmaUrl}>
                    <ArrowUpLeft aria-hidden="true" />
                    Voltar à turma
                  </Link>
                </Button>
              </Field>
            </FieldSet>
          </FieldGroup>
        </CardContent>
      </Card>
    </div>
  );
}
