"use client"

import { Loader2 } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Controller, useForm } from "react-hook-form"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldSet
} from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from "@/components/ui/select"


export function ProfessorForm({
  title,
  submitFn,
  isPending,
  disciplinas,
  professores,
  defaultValues
}) {
  const {
    handleSubmit,
    register,
    control,
    formState: { errors }
  } = useForm({
    //resolver: zodResolver(professorSchema),
    defaultValues
  })

  return (
    <div className="w-full max-w-sm px-6 py-6 mx-auto md:max-w-md lg:max-w-195">
      <form onSubmit={handleSubmit(submitFn)}>
        <Card className="overflow-visible">
          <CardHeader>
            <CardTitle>{title}</CardTitle>
            <CardDescription>Preencha os dados abaixo para definir o professor desta disciplina nesta turma</CardDescription>
          </CardHeader>

          <CardContent>
            <FieldGroup>
              <FieldSet>
                <Field>
                  <FieldLabel>Disciplina</FieldLabel>
                  <Controller
                    name="disciplina_id"
                    control={control}
                    rules={{ required: "Selecione a disciplina" }}
                    render={({ field }) => (
                      <Select
                        onValueChange={(value) => field.onChange(value)}
                        value={field.value ? field.value : ""}
                      >
                        <SelectTrigger className="w-full">
                          <SelectValue placeholder="Selecione a disciplina" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectGroup>
                            <SelectLabel>Disciplinas</SelectLabel>
                            {disciplinas?.map(disciplina => (
                              <SelectItem key={disciplina.id} value={disciplina.id}>
                                {disciplina?.disciplina?.nome ?? disciplina?.nome ?? "Sem nome"}
                              </SelectItem>
                            ))}
                          </SelectGroup>
                        </SelectContent>
                      </Select>
                    )}
                  />
                  {errors?.disciplina_id && <FieldError>{errors.disciplina_id?.message}</FieldError>}
                </Field>

                <Field>
                  <FieldLabel>Professor</FieldLabel>
                  <Controller
                    name="professor_id"
                    control={control}
                    rules={{ required: "Selecione um professor" }}
                    render={({ field }) => (
                      <Select
                        onValueChange={(value) => field.onChange(value)}
                        value={field.value ? field.value : ""}
                      >
                        <SelectTrigger className="w-full">
                          <SelectValue placeholder="Selecione o professor" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectGroup>
                            <SelectLabel>Professores do curso</SelectLabel>
                            {professores.map(professor => (
                              <SelectItem key={professor.id} value={professor.id}>
                                {professor.nome}
                              </SelectItem>
                            ))}
                          </SelectGroup>
                        </SelectContent>
                      </Select>
                    )}
                  />
                  {errors?.professor_id && <FieldError>{errors.professor_id?.message}</FieldError>}
                </Field>

                <Field>
                  <Button type="submit" disabled={isPending}>
                    {isPending ? (
                      <><Loader2 className="animate-spin" /> A guardar...</>
                    ) : (
                      <>Adicionar</>
                    )}
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