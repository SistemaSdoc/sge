import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ArrowUpLeft, Loader2 } from 'lucide-react';
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
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/spinner';

export default function AlunoForm({
  errors,
  processing,
  cursos = [],
  cursoId,
  setCursoId,
  classes = [],
  turnos = [],
  turmas = [],
  classeId,
  setClasseId,
  cursoClasseTurnoId,
  setCursoClasseTurnoId,
  turmaId,
  setTurmaId,
  defaultValues = {},
}) {
  return (
    <div className="mx-auto w-full max-w-sm p-4 md:max-w-md md:p-6 lg:max-w-195">
      <Card className="overflow-visible">
        <CardHeader className="border-b">
          <CardTitle>Editar aluno</CardTitle>
          <CardDescription>
            Actualize as informações do aluno{' '}
            <span className="font-bold">{defaultValues.nome}</span>
          </CardDescription>
        </CardHeader>

        <CardContent>
          <FieldGroup>
            <FieldSet>
              <Field>
                <FieldLabel>Matrícula</FieldLabel>
                <Input
                  readOnly
                  aria-readonly="true"
                  disabled={processing}
                  value={defaultValues.matricula ?? ''}
                />
              </Field>

              <Field>
                <FieldLabel>Nome completo</FieldLabel>
                <Input
                  name="nome"
                  disabled={processing}
                  placeholder="Ex.: João Silva"
                  defaultValue={defaultValues.nome}
                  required
                />
                {errors.nome && <FieldError>{errors.nome}</FieldError>}
              </Field>

              <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Field>
                  <FieldLabel>Nº Bilhete</FieldLabel>
                  <Input
                    name="bi"
                    disabled={processing}
                    placeholder="Ex.: 020419607LA096"
                    defaultValue={defaultValues.bi}
                    required
                  />
                  {errors.bi && <FieldError>{errors.bi}</FieldError>}
                </Field>

                <Field>
                  <FieldLabel>E-mail</FieldLabel>
                  <Input
                    name="email"
                    type="email"
                    disabled={processing}
                    placeholder="Ex.: aluno@exemplo.com"
                    defaultValue={defaultValues.email}
                    required
                  />
                  {errors.email && <FieldError>{errors.email}</FieldError>}
                </Field>
              </div>

              <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Field>
                  <FieldLabel>Curso</FieldLabel>
                  <Select
                    value={cursoId ?? ''}
                    onValueChange={setCursoId}
                    disabled={processing}
                  >
                    <SelectTrigger className="w-full">
                      <SelectValue placeholder="Selecione o curso" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectLabel>Cursos disponíveis</SelectLabel>
                        {cursos.map((curso) => (
                          <SelectItem key={curso.id} value={String(curso.id)}>
                            {curso.nome}
                          </SelectItem>
                        ))}
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  {errors.curso_tutelado_id && (
                    <FieldError>{errors.curso_tutelado_id}</FieldError>
                  )}
                </Field>

                <Field>
                  <FieldLabel>Classe</FieldLabel>
                  <Select
                    value={classeId ?? ''}
                    onValueChange={setClasseId}
                    disabled={processing}
                  >
                    <SelectTrigger className="w-full">
                      <SelectValue placeholder="Selecione a classe" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectLabel>Classes disponíveis</SelectLabel>
                        {classes.map((item) => (
                          <SelectItem key={item.id} value={String(item.id)}>
                            {item.nome}
                          </SelectItem>
                        ))}
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                </Field>
              </div>

              <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Field>
                  <FieldLabel>Turno</FieldLabel>
                  <Select
                    value={cursoClasseTurnoId ?? ''}
                    onValueChange={setCursoClasseTurnoId}
                    disabled={processing || !classeId}
                  >
                    <SelectTrigger className="w-full">
                      <SelectValue
                        placeholder={
                          !classeId
                            ? 'Selecione a classe primeiro'
                            : 'Selecione o turno'
                        }
                      />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectLabel>Turnos disponíveis</SelectLabel>
                        {turnos.map((item) => (
                          <SelectItem key={item.id} value={String(item.id)}>
                            {item.nome}
                          </SelectItem>
                        ))}
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                </Field>

                <Field>
                  <FieldLabel>Turma</FieldLabel>
                  <Select
                    value={turmaId ?? ''}
                    onValueChange={setTurmaId}
                    disabled={processing || !cursoClasseTurnoId}
                  >
                    <SelectTrigger className="w-full">
                      <SelectValue
                        placeholder={
                          !cursoClasseTurnoId
                            ? 'Selecione o turno primeiro'
                            : 'Selecione a turma'
                        }
                      />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectLabel>Turmas disponíveis</SelectLabel>
                        {turmas.map((t) => (
                          <SelectItem key={t.id} value={String(t.id)}>
                            {t.nome} — {t.classe} / {t.turno}
                          </SelectItem>
                        ))}
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  {errors.turma_id && (
                    <FieldError>{errors.turma_id}</FieldError>
                  )}
                </Field>
              </div>

              <Field>
                <Button
                  type="submit"
                  disabled={processing}
                  className="hover:cursor-pointer"
                >
                  {processing ? (
                    <>
                      <Spinner /> Salvando alterações
                    </>
                  ) : (
                    <>Salvar Alterações</>
                  )}
                </Button>

                <Button
                  type="button"
                  disabled={processing}
                  variant={'outline'}
                  className="hover:cursor-pointer"
                  onClick={() => window.history.back()}
                >
                  <ArrowUpLeft /> Voltar a lista de alunos
                </Button>
              </Field>
            </FieldSet>
          </FieldGroup>
        </CardContent>
      </Card>
    </div>
  );
}
