"use client"

import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Field, FieldError, FieldGroup, FieldLabel, FieldSet } from "@/components/ui/field"
import { Controller, useForm, useWatch } from "react-hook-form"
import MultipleSelect from "@/components/multiple-select"
import { Select, SelectContent, SelectGroup, SelectLabel, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Plus } from "lucide-react"
import { zodResolver } from "@hookform/resolvers/zod"
import { cursoInstituicaoSchema } from "../../schemas/curso-tutelado.schema"

export function CursoForm({
  title,
  classes,
  cursos,
  defaultValues = {},
  submitFn,
  isLoading,
}) {
  const {
    register,
    handleSubmit,
    control,
    setValue,
    formState: { errors }
  } = useForm({
    resolver: zodResolver(cursoInstituicaoSchema),
    defaultValues
  })

  const modo = useWatch({ control, name: 'modo' })
  const isNovoCurso = modo === 'novo'

  return (
    <div className="w-full max-w-sm px-6 py-6 mx-auto md:max-w-md lg:max-w-195">
      <form onSubmit={handleSubmit(submitFn)}>
        <Card className="overflow-visible">
          <CardHeader className="border-b">
            <CardTitle>{title}</CardTitle>
          </CardHeader>

          <CardContent>
            <FieldGroup>
              <FieldSet>
                <Field>
                  <FieldLabel htmlFor="curso_id">Curso</FieldLabel>
                  <Controller
                    name="curso_id"
                    control={control}
                    render={({ field }) => (
                      <Select
                        value={isNovoCurso ? 'novo' : field.value ? field.value : ''}
                        onValueChange={(value) => {
                          if (value === 'novo') {
                            setValue('modo', 'novo')
                            field.onChange(undefined)
                          } else {
                            setValue('modo', 'existente')
                            field.onChange(value)
                          }
                        }}
                      >
                        <SelectTrigger className="w-full">
                          <SelectValue placeholder="Selecione o curso" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectGroup>
                            <SelectLabel>Cursos</SelectLabel>
                            {cursos?.map(curso => (
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
                    )}
                  />
                  {errors.curso_id && <FieldError>{errors.curso_id.message}</FieldError>}
                </Field>

                <Field>
                  <FieldLabel htmlFor="classes">Classes</FieldLabel>
                  <Controller
                    id="classes"
                    name="classes"
                    control={control}
                    defaultValue={[]}
                    render={({ field }) => (
                      <MultipleSelect
                        isLoading={isLoading}
                        placeholder="Selecione as classes"
                        items={classes?.map(classe => ({ value: classe.id, label: classe.nome }))}
                        onChange={(opts) => field.onChange(opts.map(o => o.value))}
                        value={field.value?.map(id => ({
                          value: id,
                          label: classes?.find(c => c.id === id)?.nome ?? id
                        }))}
                      />
                    )}
                  />
                  {errors?.cursos && <FieldError>{errors.cursos?.message}</FieldError>}
                </Field>

                {isNovoCurso && (
                  <>
                    <Field>
                      <FieldLabel htmlFor="nome">Nome</FieldLabel>
                      <Input
                        id="nome"
                        type="text"
                        placeholder="Ex.: Informática de gestão"
                        {...register("nome")}
                      />
                      {errors.nome && <FieldError>{errors.nome.message}</FieldError>}
                    </Field>

                    <Field>
                      <FieldLabel htmlFor="duracao_anos">Duração (anos)</FieldLabel>
                      <Input
                        id="duracao_anos"
                        type="number"
                        placeholder="Ex.: 3"
                        {...register("duracao_anos")}
                      />
                      {errors.duracao_anos && <FieldError>{errors.duracao_anos.message}</FieldError>}
                    </Field>
                  </>
                )}

                <Field>
                  <Button type="submit" disabled={isLoading}>
                    Adicionar
                  </Button>
                </Field>
              </FieldSet>
            </FieldGroup>
          </CardContent>
        </Card>
      </form>
    </div>
  )
}