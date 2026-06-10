"use client"
import { Button } from "@/components/ui/button"
import { Loader2 } from "lucide-react"
import { zodResolver } from "@hookform/resolvers/zod"
import { Controller, useForm } from "react-hook-form"
import { bancaSchema } from "../schemas/grupo-pap.schema"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Field, FieldError, FieldGroup, FieldLabel, FieldSet } from "@/components/ui/field"
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from "@/components/ui/select"

export function BancaForm({
  title,
  isPending,
  professores = [],
  submitFn
}) {
  const { handleSubmit, control, formState: { errors } } = useForm({
    resolver: zodResolver(bancaSchema),
    defaultValues: { professor_id: "", funcao: "" }
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
                  <FieldLabel>Professor</FieldLabel>
                  <Controller
                    name="professor_id"
                    control={control}
                    render={({ field }) => (
                      <Select
                        onValueChange={(value) => field.onChange(value)}
                        value={field.value ? String(field.value) : ""}
                      >
                        <SelectTrigger className="w-full">
                          <SelectValue placeholder="Selecione o professor" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectGroup>
                            <SelectLabel>Professores</SelectLabel>
                            {professores.map(p => (
                              <SelectItem key={p.id} value={String(p.id)}>
                                {p.user.nome}
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
                  <FieldLabel>Função</FieldLabel>
                  <Controller
                    name="funcao"
                    control={control}
                    render={({ field }) => (
                      <Select
                        onValueChange={field.onChange}
                        value={field.value}
                      >
                        <SelectTrigger className="w-full">
                          <SelectValue placeholder="Selecione a função" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectGroup>
                            <SelectLabel>Funções</SelectLabel>
                            <SelectItem value="Presidente">Presidente</SelectItem>
                            <SelectItem value="Vogal 1">1º Vogal</SelectItem>
                            <SelectItem value="Vogal 2">2º Vogal</SelectItem>
                          </SelectGroup>
                        </SelectContent>
                      </Select>
                    )}
                  />
                  {errors?.funcao && <FieldError>{errors.funcao?.message}</FieldError>}
                </Field>

                <Field>
                  <Button type="submit" disabled={isPending}>
                    {isPending ? (
                      <><Loader2 className="animate-spin" /> A guardar...</>
                    ) : (
                      <>Adicionar à banca</>
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