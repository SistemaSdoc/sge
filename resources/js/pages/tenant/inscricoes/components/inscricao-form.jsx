import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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

export default function InscricaoForm({
  errors,
  processing,
  cursos = [],
  cursoId,
  setCursoId,
  cursoSelecionado,
  classeId,
  setClasseId,
  cursoClasseTurnoId,
  setCursoClasseTurnoId,
  turnoSelecionado,
  turmaId,
  setTurmaId,
  notaTeste,
  setNotaTeste,
  entityLabel = 'Matrícula',
  temNotaTeste = false,
  anoLectivoActual,
}) {
  const classes = cursoSelecionado?.classes ?? [];
  const classeSelecionada = classes.find(
    (cl) => String(cl.id) === String(classeId),
  );
  const temTurnos = classeSelecionada?.turnos?.length > 0;
  const turmas = turnoSelecionado?.turmas ?? [];
  const temTurmas = turmas.length > 0;
  const hasInstitutionError = Boolean(errors?.instituicao || errors?.message);

  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md lg:max-w-195">
      <Card>
        <CardHeader className="border-b">
          <CardTitle>{entityLabel}</CardTitle>
        </CardHeader>

        <CardContent>
          {hasInstitutionError && (
            <div
              role="alert"
              className="mb-4 rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive"
            >
              <p className="font-medium">
                Não foi possível registar a {entityLabel.toLowerCase()}.
              </p>
              <p>{errors?.instituicao ?? errors?.message}</p>
            </div>
          )}

          <FieldGroup>
            <FieldSet>
              {/* ─── Identificação mínima ─── */}
              <Field>
                <FieldLabel>Nome do estudante</FieldLabel>
                <Input
                  name="nome"
                  disabled={processing}
                  placeholder="Ex.: João Silva"
                  required
                />
                {errors.nome && <FieldError>{errors.nome}</FieldError>}
              </Field>

              <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Field>
                  <FieldLabel>Nº Bilhete de Identidade</FieldLabel>
                  <Input
                    name="bi"
                    disabled={processing}
                    placeholder="Ex.: 020419607LA096"
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
                    placeholder="Ex.: email@exemplo.com"
                    required
                  />
                  {errors.email && <FieldError>{errors.email}</FieldError>}
                </Field>
              </div>

              {/* ─── Escondidos ─── */}
              <input
                type="hidden"
                name="ano_lectivo_id"
                value={anoLectivoActual || ''}
              />
              <input
                type="hidden"
                name="curso_classe_turno_id"
                value={cursoClasseTurnoId ?? ''}
              />

              {/* ─── Curso / Classe / Turno ─── */}
              <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                <Field>
                  <FieldLabel>Curso</FieldLabel>
                  <Select
                    value={cursoId ?? ''}
                    onValueChange={setCursoId}
                    disabled={processing}
                  >
                    <SelectTrigger className="w-full">
                      <SelectValue placeholder="Selecione um curso" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        {cursos.map((c) => (
                          <SelectItem key={c.id} value={String(c.id)}>
                            {c.nome}
                          </SelectItem>
                        ))}
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  {errors.curso_id && <FieldError>{errors.curso_id}</FieldError>}
                </Field>

                <Field>
                  <FieldLabel>Classe</FieldLabel>
                  <Select
                    value={classeId ?? ''}
                    onValueChange={setClasseId}
                    disabled={processing || !cursoId}
                  >
                    <SelectTrigger className="w-full">
                      <SelectValue
                        placeholder={
                          !cursoId
                            ? 'Selecione um curso primeiro'
                            : 'Selecione uma classe'
                        }
                      />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectLabel>Classes</SelectLabel>
                        {classes.map((cl) => (
                          <SelectItem key={cl.id} value={String(cl.id)}>
                            {cl.nome}
                          </SelectItem>
                        ))}
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                </Field>

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
                            ? 'Selecione uma classe primeiro'
                            : !temTurnos
                              ? 'Nenhum turno disponível'
                              : 'Selecione um turno'
                        }
                      />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectLabel>Turnos</SelectLabel>
                        {classeSelecionada?.turnos?.map((t) => (
                          <SelectItem key={t.id} value={String(t.id)}>
                            {t.nome}
                          </SelectItem>
                        ))}
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  {errors.curso_classe_turno_id && (
                    <FieldError>{errors.curso_classe_turno_id}</FieldError>
                  )}
                </Field>
              </div>

              {/* ─── Turma + Nota ─── */}
              {temNotaTeste && (
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
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
                              ? 'Selecione um turno primeiro'
                              : !temTurmas
                                ? 'Nenhuma turma disponível'
                                : 'Selecione uma turma'
                          }
                        />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectGroup>
                          <SelectLabel>Turmas</SelectLabel>
                          {turmas.map((t) => (
                            <SelectItem key={t.id} value={String(t.id)}>
                              {t.nome}
                            </SelectItem>
                          ))}
                        </SelectGroup>
                      </SelectContent>
                    </Select>
                    {errors.turma_id && (
                      <FieldError>{errors.turma_id}</FieldError>
                    )}
                  </Field>

                  <Field>
                    <FieldLabel>Nota do Teste / Prova</FieldLabel>
                    <Input
                      name="nota_teste"
                      type="number"
                      step="0.1"
                      min="0"
                      max="20"
                      value={notaTeste}
                      onChange={(e) => setNotaTeste(e.target.value)}
                      disabled={processing}
                      placeholder="Ex.: 14.5"
                    />
                    {errors.nota_teste && (
                      <FieldError>{errors.nota_teste}</FieldError>
                    )}
                  </Field>
                </div>
              )}

              <Field>
                <Button type="submit" disabled={processing}>
                  {processing ? 'A registar…' : 'Matricular'}
                </Button>
              </Field>
            </FieldSet>
          </FieldGroup>
        </CardContent>
      </Card>
    </div>
  );
}