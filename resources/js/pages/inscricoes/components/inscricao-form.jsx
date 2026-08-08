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
  const classeSelecionada = classes?.find((cl) => String(cl.id) === String(classeId));
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
              <Field>
                <FieldLabel>Nome do estudante</FieldLabel>
                <Input
                  name="nome"
                  disabled={processing}
                  placeholder="Ex.: João Silva"
                />
                {errors.nome && <FieldError>{errors.nome}</FieldError>}
              </Field>
              <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Field>
                  <FieldLabel>Nome do pai</FieldLabel>
                  <Input
                    name="nome_pai"
                    disabled={processing}
                    placeholder="Ex.: João Silva"
                  />
                  {errors.nome_pai && (
                    <FieldError>{errors.nome_pai}</FieldError>
                  )}
                </Field>

                <Field>
                  <FieldLabel>Nome da mãe</FieldLabel>
                  <Input
                    name="nome_mae"
                    disabled={processing}
                    placeholder="Ex.: Maria Silva"
                  />
                  {errors.nome_mae && (
                    <FieldError>{errors.nome_mae}</FieldError>
                  )}
                </Field>
              </div>

              <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Field>
                  <FieldLabel>Nº Bilhete</FieldLabel>
                  <Input
                    name="bi"
                    disabled={processing}
                    placeholder="Ex.: 020419607LA096"
                  />
                  {errors.bi && <FieldError>{errors.bi}</FieldError>}
                </Field>

                <Field>
                  <FieldLabel>Data de Nascimento</FieldLabel>
                  <Input type="date" name="data_nascimento" />
                  {errors.data_nascimento && (
                    <FieldError>{errors.data_nascimento}</FieldError>
                  )}
                </Field>
              </div>

              <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Field>
                  <FieldLabel>Nacionalidade</FieldLabel>
                  <Input
                    name="nacionalidade"
                    disabled={processing}
                    placeholder="Ex.: Angolana"
                  />
                  {errors.nacionalidade && (
                    <FieldError>{errors.nacionalidade}</FieldError>
                  )}
                </Field>

                <Field>
                  <FieldLabel>Naturalidade</FieldLabel>
                  <Input
                    name="naturalidade"
                    disabled={processing}
                    placeholder="Ex.: Luanda"
                  />
                  {errors.naturalidade && (
                    <FieldError>{errors.naturalidade}</FieldError>
                  )}
                </Field>
              </div>


                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <Field>
                    <FieldLabel>Município</FieldLabel>
                    <Input
                      name="municipio"
                      disabled={processing}
                      placeholder="Ex.: Belas"
                    />
                    {errors.municipio && <FieldError>{errors.municipio}</FieldError>}
                  </Field>
                  </div>

              <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Field>
                  <FieldLabel>Morada</FieldLabel>
                  <Input
                    name="morada"
                    disabled={processing}
                    placeholder="Ex.: Luanda Sul"
                  />
                  {errors.morada && <FieldError>{errors.morada}</FieldError>}
                </Field>
                <Field>
                  <FieldLabel>Género</FieldLabel>
                  <Select name="genero" disabled={processing} defaultValue="M">
                    <SelectTrigger className="w-full">
                      <SelectValue placeholder="Selecione um género" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectLabel>Géneros</SelectLabel>
                        <SelectItem value="M">Masculino</SelectItem>
                        <SelectItem value="F">Feminino</SelectItem>
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  {errors.genero && <FieldError>{errors.genero}</FieldError>}
                </Field>
              </div>

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

              <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Field>
                  <FieldLabel>Telefone</FieldLabel>
                  <div className="flex">
                    <span className="flex items-center justify-center border border-r-0 px-3 py-1 text-xs text-muted-foreground">
                      +244
                    </span>
                    <Input
                      name="telefone"
                      type="tel"
                      disabled={processing}
                      placeholder="Ex.: 950000000"
                    />
                  </div>
                  {errors.telefone && (
                    <FieldError>{errors.telefone}</FieldError>
                  )}
                </Field>

                <Field>
                  <FieldLabel>E-mail</FieldLabel>
                  <Input
                    name="email"
                    type="email"
                    disabled={processing}
                    placeholder="Ex.: email@exemplo.com"
                  />
                  {errors.email && <FieldError>{errors.email}</FieldError>}
                </Field>
              </div>

              {/* <Field>
                <FieldLabel>{entityLabel}</FieldLabel>
                <Select
                  value={instituicaoId}
                  onValueChange={setInstituicaoId}
                  disabled={processing}
                >
                  <SelectTrigger className="w-full">
                    <SelectValue placeholder={`Selecione ${entityLabelText.toLowerCase()}`} />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      <SelectLabel>Instituições</SelectLabel>
                      {instituicoes.map((i) => (
                        <SelectItem key={i.id} value={String(i.id)}>
                          {i.nome}
                        </SelectItem>
                      ))}
                    </SelectGroup>
                  </SelectContent>
                </Select>
                {errors.instituicao_id && (
                  <FieldError>{errors.instituicao_id}</FieldError>
                )}
              </Field> */}

              <input
                type="hidden"
                name="ano_lectivo_id"
                value={anoLectivoActual || ''}
              />

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
                        placeholder={!cursoId ? 'Selecione um curso primeiro' : 'Selecione uma classe'}
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
                                : 'Selecione uma turma (opcional)'
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
                      type="number"
                      step="0.1"
                      min="0"
                      max="20"
                      value={notaTeste}
                      onChange={(e) => setNotaTeste(e.target.value)}
                      disabled={processing}
                      placeholder="Ex.: 14.5 (opcional)"
                    />
                    {errors.nota_teste && (
                      <FieldError>{errors.nota_teste}</FieldError>
                    )}
                  </Field>
                </div>
              )}
              {/*  nº de estudante
              <Field>
                <FieldLabel>Nº Estudante</FieldLabel>
                <Input
                  name="numero_estudante"
                  disabled
                  placeholder="Ex.: ES2026/034"
                />
                {errors.numero_estudante && (
                  <FieldError>{errors.numero_estudante}</FieldError>
                )}
              </Field>
                  */}
              <Field>
                <Button type="submit" disabled={processing}>
                  Matricular
                </Button>
              </Field>
            </FieldSet>
          </FieldGroup>
        </CardContent>
      </Card>
    </div>
  );
}
