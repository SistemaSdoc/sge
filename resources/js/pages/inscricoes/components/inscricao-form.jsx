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
  cursoClasseTurnoId,
  setCursoClasseTurnoId,
}) {
  const temTurnos = cursoSelecionado?.turnos?.length > 0;

  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md lg:max-w-195">
      <Card>
        <CardHeader className="border-b">
          <CardTitle>Inscrição</CardTitle>
        </CardHeader>

        <CardContent>
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
                  <FieldLabel>Nº Bilhete</FieldLabel>
                  <Input
                    name="bi"
                    disabled={processing}
                    placeholder="Ex.: 020419607LA096"
                  />
                  {errors.bi && <FieldError>{errors.bi}</FieldError>}
                </Field>

                <Field>
                  <FieldLabel>Nº Estudante</FieldLabel>
                  <Input
                    name="numero_estudante"
                    disabled={processing}
                    placeholder="Ex.: ES2026/034"
                  />
                  {errors.numero_estudante && (
                    <FieldError>{errors.numero_estudante}</FieldError>
                  )}
                </Field>
              </div>

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

              <Field>
                <FieldLabel>Morada</FieldLabel>
                <Input
                  name="morada"
                  disabled={processing}
                  placeholder="Ex.: Luanda Sul"
                />
                {errors.morada && <FieldError>{errors.morada}</FieldError>}
              </Field>

              {/* <Field>
                <FieldLabel>Instituição</FieldLabel>
                <Select
                  value={instituicaoId}
                  onValueChange={setInstituicaoId}
                  disabled={processing}
                >
                  <SelectTrigger className="w-full">
                    <SelectValue placeholder="Selecione a instituição" />
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

              <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Field>
                  <FieldLabel>Curso</FieldLabel>
                  <Select
                    value={cursoId}
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
                  <FieldLabel>Turno</FieldLabel>
                  <Select
                    value={cursoClasseTurnoId}
                    onValueChange={setCursoClasseTurnoId}
                    disabled={processing || !cursoId}
                  >
                    <SelectTrigger className="w-full">
                      <SelectValue
                        placeholder={
                          !cursoId
                            ? 'Selecione um curso primeiro'
                            : !temTurnos
                              ? 'Nenhum turno disponível'
                              : 'Selecione um turno'
                        }
                      />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectLabel>Turnos</SelectLabel>
                        {cursoSelecionado?.turnos?.map((t) => (
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

              <Field>
                <Button type="submit" disabled={processing}>
                  Inscrever
                </Button>
              </Field>
            </FieldSet>
          </FieldGroup>
        </CardContent>
      </Card>
    </div>
  );
}
