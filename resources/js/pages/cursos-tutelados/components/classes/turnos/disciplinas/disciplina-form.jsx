"use client"

import { Loader2 } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Controller, useForm } from "react-hook-form"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldSet
} from "@/components/ui/field"
import MultipleSelect from "@/components/multiple-select"

export default function DisciplinaForm({
  disciplinas,
  submitFn,
  isLoading,
  isPending,
  defaultValues
}) {
  const {
    handleSubmit,
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
            <CardTitle>Associar Disciplinas</CardTitle>
          </CardHeader>

          <CardContent>
            <FieldGroup>
              <FieldSet>
                <Field>
                  <FieldLabel>Disciplinas</FieldLabel>

                  <Controller
                    id="disciplina_ids"
                    name="disciplina_ids"
                    control={control}
                    defaultValue={[]}
                    render={({ field }) => (
                      <MultipleSelect
                        isLoading={isLoading}
                        placeholder="Selecione as disciplinas"
                        items={disciplinas?.map(disciplina => {
                          return { value: disciplina.id, label: disciplina.nome }
                        })}
                        onChange={(opts) => field.onChange(opts.map(o => o.value))}
                        value={field.value?.map(id => ({
                          value: id,
                          label: disciplinas?.find(disciplina => disciplina.id === id)?.nome ?? id
                        }))}
                      />
                    )}
                  />

                  {errors.disciplina_ids && (
                    <FieldError>{errors.disciplina_ids.message}</FieldError>
                  )}
                </Field>

                <Field>
                  <Button type="submit" disabled={isPending}>
                    {isPending ? (
                      <Loader2 className="animate-spin" />
                    ) : (
                      "Associar"
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