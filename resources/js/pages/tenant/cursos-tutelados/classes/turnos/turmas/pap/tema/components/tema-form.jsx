import { Loader2 } from 'lucide-react';

import { Button } from '@/components/ui/button';
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
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

export function TemaForm({
  title,
  description,
  errors,
  processing,
  data,
  setData,
  submitFn,
  professores = [],
  sugestoesTemas = [],
  mode = 'create',
  showStudyCase = true,
  actions,
}) {
  const fieldProps = (name) => ({
    value: data[name] ?? '',
    onChange: (event) => setData(name, event.target.value),
  });

  return (
    <div className="mx-auto w-full max-w-sm p-4 md:max-w-md md:p-6 lg:max-w-195">
      <form onSubmit={submitFn}>
        <Card className="overflow-visible">
          <CardHeader className="border-b">
            <CardTitle>{title}</CardTitle>
            {description && <CardDescription>{description}</CardDescription>}
          </CardHeader>

          <CardContent>
            <FieldGroup>
              <FieldSet>
                {mode === 'create' && (
                  <Field>
                    <FieldLabel>Professor tutor</FieldLabel>
                    <Select
                      value={data.professor_tutor_id || undefined}
                      onValueChange={(value) =>
                        setData('professor_tutor_id', value)
                      }
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
                )}

                {mode === 'create' && (
                  <Field data-invalid={Boolean(errors.origem_tema)}>
                    <FieldLabel>Escolha do tema</FieldLabel>
                    <div className="grid gap-2 sm:grid-cols-2">
                      <Button
                        type="button"
                        variant={
                          data.origem_tema === 'sugerido'
                            ? 'default'
                            : 'outline'
                        }
                        aria-pressed={data.origem_tema === 'sugerido'}
                        disabled={processing || sugestoesTemas.length === 0}
                        onClick={() => {
                          setData('tema_sugerido_id', '');
                          setData('origem_tema', 'sugerido');
                        }}
                      >
                        Escolher tema sugerido
                      </Button>
                      <Button
                        type="button"
                        variant={
                          data.origem_tema === 'autoral' ? 'default' : 'outline'
                        }
                        aria-pressed={data.origem_tema === 'autoral'}
                        disabled={processing}
                        onClick={() => {
                          setData('tema_sugerido_id', '');
                          setData('origem_tema', 'autoral');
                        }}
                      >
                        Propor tema do grupo
                      </Button>
                    </div>
                    {errors.origem_tema && (
                      <FieldError>{errors.origem_tema}</FieldError>
                    )}

                    {data.origem_tema === 'sugerido' ? (
                      <>
                        <Select
                          value={data.tema_sugerido_id || undefined}
                          onValueChange={(value) =>
                            setData('tema_sugerido_id', value)
                          }
                          disabled={processing}
                        >
                          <SelectTrigger
                            id="tema-sugerido-select"
                            className="w-full"
                          >
                            <SelectValue placeholder="Selecione um tema sugerido" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectGroup>
                              <SelectLabel>
                                Temas sugeridos para o curso
                              </SelectLabel>
                              {sugestoesTemas.map((sugestao) => (
                                <SelectItem
                                  key={sugestao.id}
                                  value={String(sugestao.id)}
                                >
                                  {sugestao.titulo}
                                </SelectItem>
                              ))}
                            </SelectGroup>
                          </SelectContent>
                        </Select>
                        {errors.tema_sugerido_id && (
                          <FieldError>{errors.tema_sugerido_id}</FieldError>
                        )}
                        {sugestoesTemas.length === 0 && (
                          <p className="text-sm text-muted-foreground">
                            Não existem temas sugeridos. Proponha um tema do
                            grupo.
                          </p>
                        )}
                      </>
                    ) : null}
                  </Field>
                )}

                {(mode === 'edit' || data.origem_tema === 'autoral') && (
                  <Field>
                    <FieldLabel>Tema do Grupo</FieldLabel>
                    <Input
                      name="tema_grupo"
                      disabled={processing}
                      placeholder="Ex.: Sistema de Gestão Escolar"
                      aria-invalid={Boolean(errors.tema_grupo)}
                      {...fieldProps('tema_grupo')}
                    />
                    {errors.tema_grupo && (
                      <FieldError>{errors.tema_grupo}</FieldError>
                    )}
                  </Field>
                )}

                <Field>
                  <FieldLabel>Problema</FieldLabel>
                  <Textarea
                    name="problema"
                    disabled={processing}
                    placeholder="Ex.: Dificuldades na gestão de alunos e professores"
                    aria-invalid={Boolean(errors.problema)}
                    {...fieldProps('problema')}
                  />
                  {errors.problema && (
                    <FieldError>{errors.problema}</FieldError>
                  )}
                </Field>

                <Field>
                  <FieldLabel>Objectivo Geral</FieldLabel>
                  <Textarea
                    name="objectivo_geral"
                    disabled={processing}
                    placeholder="Ex.: Desenvolver um sistema de gestão escolar"
                    aria-invalid={Boolean(errors.objectivo_geral)}
                    {...fieldProps('objectivo_geral')}
                  />
                  {errors.objectivo_geral && (
                    <FieldError>{errors.objectivo_geral}</FieldError>
                  )}
                </Field>

                <Field>
                  <FieldLabel>Objectivos Específicos</FieldLabel>
                  <Textarea
                    name="objectivos_especificos"
                    disabled={processing}
                    placeholder="Descreve os objectivos específicos..."
                    aria-invalid={Boolean(errors.objectivos_especificos)}
                    {...fieldProps('objectivos_especificos')}
                  />
                  {errors.objectivos_especificos && (
                    <FieldError>{errors.objectivos_especificos}</FieldError>
                  )}
                </Field>

                {showStudyCase && (
                  <Field>
                    <FieldLabel>Estudo de caso</FieldLabel>
                    <Input
                      name="estudo_caso"
                      disabled={processing}
                      placeholder="Descreve o estudo de caso..."
                      aria-invalid={Boolean(errors.estudo_caso)}
                      {...fieldProps('estudo_caso')}
                    />
                    {errors.estudo_caso && (
                      <FieldError>{errors.estudo_caso}</FieldError>
                    )}
                  </Field>
                )}

                <Field>
                  {actions ?? (
                    <Button type="submit" disabled={processing}>
                      {processing ? (
                        <>
                          <Loader2 className="animate-spin" /> A guardar...
                        </>
                      ) : (
                        <>Guardar</>
                      )}
                    </Button>
                  )}
                </Field>
              </FieldSet>
            </FieldGroup>
          </CardContent>
        </Card>
      </form>
    </div>
  );
}
