import { useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldSet,
} from '@/components/ui/field';
import MultipleSelect from '@/components/multiple-select';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectLabel,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Plus } from 'lucide-react';

export function CursoForm({
  title,
  classes,
  cursos,
  data,
  setData,
  errors,
  processing,
  onSubmit,
}) {
  const [modo, setModo] = useState('existente');
  const isNovoCurso = modo === 'novo';

  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md lg:max-w-195">
      <form onSubmit={onSubmit}>
        <Card className="overflow-visible">
          <CardHeader className="border-b">
            <CardTitle>{title}</CardTitle>
          </CardHeader>

          <CardContent>
            <FieldGroup>
              <FieldSet>
                <Field>
                  <FieldLabel htmlFor="curso_id">Curso</FieldLabel>
                  <Select
                    value={isNovoCurso ? 'novo' : data.curso_id}
                    onValueChange={(value) => {
                      if (value === 'novo') {
                        setModo('novo');
                        setData('curso_id', '');
                      } else {
                        setModo('existente');
                        setData('curso_id', value);
                      }
                    }}
                  >
                    <SelectTrigger className="w-full">
                      <SelectValue placeholder="Selecione o curso" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectLabel>Cursos</SelectLabel>
                        {cursos?.map((curso) => (
                          <SelectItem key={curso.id} value={curso.id}>
                            {curso.nome}
                          </SelectItem>
                        ))}
                        <SelectItem value="novo">
                          <Plus className="size-3!" /> Novo curso
                        </SelectItem>
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  {errors.curso_id && (
                    <FieldError>{errors.curso_id}</FieldError>
                  )}
                </Field>

                <Field>
                  <FieldLabel htmlFor="classes">Classes</FieldLabel>
                  <MultipleSelect
                    placeholder="Selecione as classes"
                    items={classes?.map((classe) => ({
                      value: classe.id,
                      label: classe.nome,
                    }))}
                    onChange={(opts) =>
                      setData(
                        'classes',
                        opts.map((o) => o.value),
                      )
                    }
                    value={data.classes.map((id) => ({
                      value: id,
                      label: classes?.find((c) => c.id === id)?.nome ?? id,
                    }))}
                  />
                  {errors.classes && <FieldError>{errors.classes}</FieldError>}
                </Field>

                {isNovoCurso && (
                  <>
                    <Field>
                      <FieldLabel htmlFor="nome">Nome</FieldLabel>
                      <Input
                        id="nome"
                        type="text"
                        placeholder="Ex.: Informática de gestão"
                        value={data.nome}
                        onChange={(e) => setData('nome', e.target.value)}
                      />
                      {errors.nome && <FieldError>{errors.nome}</FieldError>}
                    </Field>

                    <Field>
                      <FieldLabel htmlFor="duracao_anos">
                        Duração (anos)
                      </FieldLabel>
                      <Input
                        id="duracao_anos"
                        type="number"
                        placeholder="Ex.: 3"
                        value={data.duracao_anos}
                        onChange={(e) =>
                          setData('duracao_anos', e.target.value)
                        }
                      />
                      {errors.duracao_anos && (
                        <FieldError>{errors.duracao_anos}</FieldError>
                      )}
                    </Field>
                  </>
                )}

                <Field>
                  <Button type="submit" disabled={processing}>
                    Adicionar
                  </Button>
                </Field>
              </FieldSet>
            </FieldGroup>
          </CardContent>
        </Card>
      </form>
    </div>
  );
}
