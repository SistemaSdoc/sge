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

export function TemaForm({
  title,
  errors,
  processing,
  grupoPap,
  professores = [],
  sugestoesTemas = [],
}) {
  const [professorTutorId, setProfessorTutorId] = useState(undefined);
  const [origemTema, setOrigemTema] = useState(
    sugestoesTemas.length > 0 ? 'sugerido' : 'autoral',
  );
  const [sugestaoTemaId, setSugestaoTemaId] = useState('');

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
                <FieldLabel>Professor tutor (obrigatório)</FieldLabel>
                <input
                  type="hidden"
                  name="professor_tutor_id"
                  value={professorTutorId}
                />
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
                <FieldLabel>Escolha do tema</FieldLabel>
                <input type="hidden" name="origem_tema" value={origemTema} />
                <div className="grid gap-2 sm:grid-cols-2">
                  <Button
                    type="button"
                    variant={origemTema === 'sugerido' ? 'default' : 'outline'}
                    aria-pressed={origemTema === 'sugerido'}
                    disabled={processing || sugestoesTemas.length === 0}
                    onClick={() => {
                      setSugestaoTemaId('');
                      setOrigemTema('sugerido');
                    }}
                  >
                    Escolher tema sugerido
                  </Button>
                  <Button
                    type="button"
                    variant={origemTema === 'autoral' ? 'default' : 'outline'}
                    aria-pressed={origemTema === 'autoral'}
                    disabled={processing}
                    onClick={() => {
                      setSugestaoTemaId('');
                      setOrigemTema('autoral');
                    }}
                  >
                    Propor tema do grupo
                  </Button>
                </div>

                {origemTema === 'sugerido' ? (
                  <>
                    <input
                      type="hidden"
                      name="tema_sugerido_id"
                      value={sugestaoTemaId}
                    />
                    <Select
                      value={sugestaoTemaId || undefined}
                      onValueChange={setSugestaoTemaId}
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
                        Não existem temas sugeridos. Proponha um tema do grupo.
                      </p>
                    )}
                  </>
                ) : (
                  <Input
                    name="tema_grupo"
                    disabled={processing}
                    placeholder="Ex.: Sistema de Gestão Escolar"
                    defaultValue={grupoPap?.tema_grupo ?? ''}
                  />
                )}
                {errors.tema_grupo && (
                  <FieldError>{errors.tema_grupo}</FieldError>
                )}
              </Field>

              <Field>
                <FieldLabel>Problema (obrigatório)</FieldLabel>
                <Input
                  name="problema"
                  disabled={processing}
                  placeholder="Ex.: Dificuldades na gestão de alunos e professores"
                  defaultValue={grupoPap?.problema ?? ''}
                />
                {errors.problema && <FieldError>{errors.problema}</FieldError>}
              </Field>

              <Field>
                <FieldLabel>Objectivos (obrigatório)</FieldLabel>
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
                <FieldLabel>Estudo de caso (obrigatório)</FieldLabel>
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
