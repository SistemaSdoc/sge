"use client"

import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { zodResolver } from "@hookform/resolvers/zod"
import { turmaSchema } from "../schemas/turma.schema"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Field, FieldError, FieldGroup, FieldLabel, FieldSet } from "@/components/ui/field"
import { Controller, useForm } from "react-hook-form"
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectLabel,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"

export function TurmaForm({
  title,
  classes,
  defaultValues = {},
  submitFn
}) {
  const {
    register,
    handleSubmit,
    control,
    formState: { errors }
  } = useForm({
    resolver: zodResolver(turmaSchema),
    defaultValues
  })

  return (
    <div className="w-full max-w-sm px-6 py-6 mx-auto md:max-w-md lg:max-w-195">
      <form onSubmit={handleSubmit(submitFn)}>
        <Card>
          <CardHeader className="border-b">
            <CardTitle>{title}</CardTitle>
          </CardHeader>

          <CardContent>
            <FieldGroup>
              <FieldSet>
                <Field>
                  <FieldLabel htmlFor="nome">Nome</FieldLabel>
                  <Input
                    id="nome"
                    type="text"
                    disabled={false}
                    placeholder="ATI"
                    {...register("nome")}
                  />
                  {errors?.nome && <FieldError>{errors.nome?.message}</FieldError>}
                </Field>

                <Field>
                  <FieldLabel htmlFor="max_alunos">Máx. Alunos</FieldLabel>
                  <Input
                    id="max_alunos"
                    type="number"
                    disabled={false}
                    placeholder="Ex.: 50"
                    {...register("max_alunos")}
                  />
                  {errors?.max_alunos && <FieldError>{errors.max_alunos?.message}</FieldError>}
                </Field>

                <Field>
                  <FieldLabel htmlFor="classe_id">Classe</FieldLabel>
                  <Controller
                    id="classe_id"
                    name="classe_id"
                    control={control}
                    defaultValue=""
                    render={({ field }) => (
                      <Select
                        onValueChange={(value) => field.onChange(value)}
                        value={field.value}
                      >
                        <SelectTrigger className="w-full">
                          <SelectValue placeholder="Selecione uma classe" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectGroup>
                            <SelectLabel>Classes</SelectLabel>
                            {classes.map(classe => (
                              <SelectItem value={classe.id}>{classe.nome}</SelectItem>
                            ))}
                          </SelectGroup>
                        </SelectContent>
                      </Select>
                    )}
                  />
                  {errors?.classe_id && <FieldError>{errors.classe_id?.message}</FieldError>}
                </Field>

                <Field>
                  <Button type="submit" disabled={false}>
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