"use client"

import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Field, FieldError, FieldGroup, FieldLabel, FieldSet } from "@/components/ui/field"
import { Controller, useForm } from "react-hook-form"
import MultipleSelect from "@/components/multiple-select"
import { Select, SelectContent, SelectGroup, SelectLabel, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { zodResolver } from "@hookform/resolvers/zod"
import { cursoTuteladoEditSchema } from "../../schemas/curso-tutelado-edit.schema"

export function CursoForm({
  title,
  classes,
  instituicoes,
  defaultValues = {},
  submitFn,
  isLoading,
}) {
  const {
    register,
    handleSubmit,
    control,
    formState: { errors }
  } = useForm({
    resolver: zodResolver(cursoTuteladoEditSchema),
    defaultValues
  })

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
                  <FieldLabel htmlFor="duracao_anos">Duração (anos)</FieldLabel>
                  <Input
                    id="duracao_anos"
                    type="number"
                    placeholder="Ex.: 3"
                    {...register("duracao_anos")}
                  />
                  {errors.duracao_anos && <FieldError>{errors.duracao_anos.message}</FieldError>}
                </Field>

                <Field>
                  <FieldLabel htmlFor="classes">Classes</FieldLabel>
                  <Controller
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
                  {errors.classes && <FieldError>{errors.classes.message}</FieldError>}
                </Field>

                <Field>
                  <FieldLabel htmlFor="instituicao_tutora_id">Instituição Tutora</FieldLabel>
                  <Controller
                    name="instituicao_tutora_id"
                    control={control}
                    render={({ field }) => (
                      <Select
                        value={field.value ? field.value : ""}
                        onValueChange={(value) => field.onChange(value)}
                      >
                        <SelectTrigger className="w-full">
                          <SelectValue placeholder="Selecione a instituição tutora" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectGroup>
                            <SelectLabel>Instituições</SelectLabel>
                            {instituicoes?.map(inst => (
                              <SelectItem key={inst.id} value={inst.id}>
                                {inst.nome}
                              </SelectItem>
                            ))}
                          </SelectGroup>
                        </SelectContent>

                      </Select>
                    )}
                  />
                  {errors.instituicao_tutora_id && <FieldError>{errors.instituicao_tutora_id.message}</FieldError>}
                </Field>

                <Field>
                  <Button type="submit" disabled={isLoading}>
                    Guardar
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