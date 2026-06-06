"use client"
import { Loader2 } from "lucide-react"
import { Button } from "@/components/ui/button"
import { zodResolver } from "@hookform/resolvers/zod"
import { Controller, useForm } from "react-hook-form"
import { professorSchema } from "../../schemas/professor.schema"
import { Card, CardAction, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Field, FieldError, FieldGroup, FieldLabel, FieldSet } from "@/components/ui/field"
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from "@/components/ui/select"
import MultipleSelect from "@/components/multiple-select"
import { Switch } from "@/components/ui/switch"

export function ProfessorForm({
  title,
  isPending,
  professores = [],
  isLoadingTurnos,
  turnos = [],
  defaultValues = {},
  submitFn
}) {
  const {
    handleSubmit,
    control,
    formState: { errors }
  } = useForm({
    resolver: zodResolver(professorSchema),
    defaultValues
  })

  return (
    <div className="w-full max-w-sm px-6 py-6 mx-auto md:max-w-md lg:max-w-195">
      <form onSubmit={handleSubmit(submitFn)}>
        <Card className="overflow-visible">
          <CardHeader className="border-b">
            <CardTitle>{title}</CardTitle>

            <CardAction>
              <Field orientation="horizontal" className="w-fit">
                <FieldLabel htmlFor="coordenador">Coordenador</FieldLabel>
                <Controller
                  name="coordenador"
                  control={control}
                  render={({ field }) => (
                    <Switch
                      size="sm"
                      id="coordenador"
                      checked={field.value}
                      onCheckedChange={field.onChange}
                    />
                  )}
                />
              </Field>
            </CardAction>
          </CardHeader>

          <CardContent>
            <FieldGroup>
              <FieldSet>
                <Field>
                  <FieldLabel htmlFor="professor_id">Professor</FieldLabel>
                  <Controller
                    name="professor_id"
                    control={control}
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
                            <SelectLabel>Professores</SelectLabel>
                            {professores.map(p => (
                              <SelectItem key={p.id} value={p.id}>
                                {<p className="user">{p.user.nome}</p>}
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
                  <FieldLabel htmlFor="professor_id">Tipo</FieldLabel>
                  <Controller
                    name="tipo"
                    control={control}
                    render={({ field }) => (
                      <Select
                        onValueChange={(value) => field.onChange(String(value))}
                        value={field.value ? String(field.value) : ""}
                      >
                        <SelectTrigger className="w-full">
                          <SelectValue placeholder="Selecione o tipo" />
                        </SelectTrigger>

                        <SelectContent>
                          <SelectGroup>
                            <SelectLabel>Tipos</SelectLabel>
                            <SelectItem value="principal">Principal</SelectItem>
                            <SelectItem value="colaborador">Colaborador</SelectItem>
                          </SelectGroup>
                        </SelectContent>
                      </Select>
                    )}
                  />
                  {errors?.tipo && <FieldError>{errors.tipo?.message}</FieldError>}
                </Field>
                <Field>
                  <Button type="submit" disabled={isPending}>
                    {isPending ? (
                      <><Loader2 className="animate-spin" /> A guardar...</>
                    ) : (
                      <>Guardar</>
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