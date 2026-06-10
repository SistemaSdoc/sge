"use client"

import * as React from "react"
import { Button } from "@/components/ui/button"
import { Loader2 } from "lucide-react"
import { Controller, useForm } from "react-hook-form"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Field, FieldError, FieldGroup, FieldLabel, FieldSet } from "@/components/ui/field"
import { Input } from "@/components/ui/input"

export function DataDefesaForm({
  title,
  isPending,
  submitFn
}) {
  const {
    handleSubmit,
    control,
    formState: { errors }
  } = useForm({
    defaultValues: {
      data_defesa: undefined,
      local_defesa: ""
    }
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
                  <FieldLabel>Data da Defesa</FieldLabel>
                  <Controller
                    name="data_defesa"
                    control={control}
                    render={({ field }) => (
                      <Input
                        {...field}
                        type="date"
                        aria-invalid={!!errors.local}
                      />
                    )}
                  />
                  {errors.data_defesa && (
                    <FieldError>{errors.data_defesa.message}</FieldError>
                  )}
                </Field>

                <Field>
                  <FieldLabel>Local</FieldLabel>
                  <Controller
                    name="local_defesa"
                    control={control}
                    render={({ field }) => (
                      <Input
                        {...field}
                        type="text"
                        placeholder="Ex: Sala 101, Bloco A"
                        aria-invalid={!!errors.local}
                      />
                    )}
                  />
                  {errors.local && <FieldError>{errors.local.message}</FieldError>}
                </Field>

                <Field>
                  <Button
                    type="submit"
                    disabled={isPending}
                    className="w-full"
                  >
                    {isPending ? (
                      <><Loader2 className="mr-2 h-4 w-4 animate-spin" /> Definindo...</>
                    ) : (
                      "Definir data"
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