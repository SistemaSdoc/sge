"use client"
import { useState } from "react"
import { useRouter } from "next/navigation"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Field, FieldError, FieldLabel, FieldGroup, FieldSet } from "@/components/ui/field"
import { Select, SelectContent, SelectGroup, SelectLabel, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import MultipleSelect from "@/components/multiple-select"
import Loader from "@/components/loader"

export default function Create({ instituicaoId, cursoId }) {
  

  return (
    <div className="w-full max-w-sm px-6 py-6 mx-auto md:max-w-md lg:max-w-195">
      <form onSubmit={handleSubmit((data) => {
        mutation.mutate({
          cursoClasseId: data.cursoClasseId,
          turnos: data.turnos,
        }, {
          onSuccess: () => router.push(`/dashboard/instituicoes/${instituicaoId}/cursos/${cursoId}`)
        })
      })}>
        <Card className="overflow-visible">
          <CardHeader className="border-b">
            <CardTitle>Definir Turnos por Classe</CardTitle>
          </CardHeader>

          <CardContent>
            <FieldGroup>
              <FieldSet>
                <Field>
                  <FieldLabel>Classe</FieldLabel>
                  <Controller
                    name="cursoClasseId"
                    control={control}
                    render={({ field }) => (
                      <Select
                        value={field.value ? field.value : undefined}
                        onValueChange={handleClasseChange}
                      >
                        <SelectTrigger className="w-full">
                          <SelectValue placeholder="Selecione a classe" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectGroup>
                            <SelectLabel>Classes</SelectLabel>
                            {classesTurnos?.map(cc => (
                              <SelectItem key={cc.id} value={String(cc.id)}>
                                {cc.classe.nome}
                              </SelectItem>
                            ))}
                          </SelectGroup>
                        </SelectContent>
                      </Select>
                    )}
                  />
                </Field>

                <Field>
                  <FieldLabel>Turnos</FieldLabel>
                  <Controller
                    name="turnos"
                    control={control}
                    defaultValue={[]}
                    render={({ field }) => (
                      <MultipleSelect
                        placeholder="Selecione os turnos"
                        items={turnos?.map(t => ({ value: t.id, label: t.nome }))}
                        onChange={(opts) => field.onChange(opts.map(o => o.value))}
                        value={field.value?.map(id => {
                          const classeActual = classesTurnos?.find(c => c.id === cursoClasseId)
                          const turnoNaClasse = classeActual?.turnos?.find(t => t.id === id)
                          const turnoGlobal = turnos?.find(t => t.id === id)
                          return {
                            value: id,
                            label: turnoNaClasse?.nome ?? turnoGlobal?.nome ?? id
                          }
                        })}
                        disabled={!cursoClasseId}
                      />
                    )}
                  />
                </Field>

                <Field>
                  <Button type="submit" disabled={!cursoClasseId || mutation.isPending}>
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