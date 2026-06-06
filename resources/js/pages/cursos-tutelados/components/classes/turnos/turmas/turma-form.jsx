"use client"

import { Loader2 } from "lucide-react"
import { Button } from "@/components/ui/button"
import { useForm } from "react-hook-form"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldSet
} from "@/components/ui/field"
import { Input } from "@/components/ui/input"

export function TurmaForm({
  submitFn,
  isPending,
  defaultValues
}) {
  const {
    handleSubmit,
    register,
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
            <CardTitle>Criar Turma</CardTitle>
            <CardDescription>Preencha os dados abaixo para criar a turma</CardDescription>
          </CardHeader>

          <CardContent>
            <FieldGroup>
              <FieldSet>
                <Field>
                  <FieldLabel htmlFor="nome">Nome</FieldLabel>
                  <Input
                    id="nome"
                    type="text"
                    disabled={isPending}
                    placeholder="Ex.: Turma A"
                    {...register("nome")}
                  />
                  {errors?.nome && <FieldError>{errors.nome?.message}</FieldError>}
                </Field>

                <Field>
                  <FieldLabel htmlFor="max_alunos">Máximo de alunos</FieldLabel>
                  <Input
                    id="max_alunos"
                    type="number"
                    disabled={isPending}
                    placeholder="Ex.: 30"
                    {...register("max_alunos")}
                  />
                  {errors?.max_alunos && <FieldError>{errors.max_alunos?.message}</FieldError>}
                </Field>

                <Field>
                  <Button type="submit" disabled={isPending}>
                    {isPending ? (
                      <Loader2 className="animate-spin" />
                    ) : (
                      "Criar"
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