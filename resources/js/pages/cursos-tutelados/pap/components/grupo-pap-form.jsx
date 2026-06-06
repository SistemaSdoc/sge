"use client";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Loader2 } from "lucide-react";
import { zodResolver } from "@hookform/resolvers/zod";
import { Controller, useForm } from "react-hook-form";
import { grupoPapSchema } from "../schemas/grupo-pap.schema";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldSet,
} from "@/components/ui/field";
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import MultipleSelect from "@/components/multiple-select";

export function GrupoPapForm({
  title,
  isPending,
  professores = [],
  alunos = [],
  defaultValues = {},
  submitFn,
}) {
  const {
    register,
    handleSubmit,
    control,
    formState: { errors },
  } = useForm({
    resolver: zodResolver(grupoPapSchema),
    defaultValues,
  });

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
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <Field>
                    <FieldLabel>Nome do grupo</FieldLabel>
                    <Input
                      disabled={isPending}
                      placeholder="Ex.: Grupo Alpha"
                      {...register("nome_grupo")}
                    />
                    {errors?.nome_grupo && (
                      <FieldError>{errors.nome_grupo?.message}</FieldError>
                    )}
                  </Field>

                  <Field>
                    <FieldLabel>Tema</FieldLabel>
                    <Input
                      disabled={isPending}
                      placeholder="Ex.: Sistema de Gestão Escolar"
                      {...register("tema_grupo")}
                    />
                    {errors?.tema_grupo && (
                      <FieldError>{errors.tema_grupo?.message}</FieldError>
                    )}
                  </Field>
                </div>

                <Field>
                  <FieldLabel>Professor tutor</FieldLabel>
                  <Controller
                    name="professor_tutor_id"
                    control={control}
                    render={({ field }) => (
                      <Select
                        onValueChange={(value) => field.onChange(value)}
                        value={field.value ? field.value : ""}
                      >
                        <SelectTrigger className="w-full">
                          <SelectValue placeholder="Selecione o professor tutor" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectGroup>
                            <SelectLabel>Professores</SelectLabel>
                            {professores?.map((p) => (
                              <SelectItem key={p.id} value={p.id}>
                                {p.nome}
                              </SelectItem>
                            ))}
                          </SelectGroup>
                        </SelectContent>
                      </Select>
                    )}
                  />
                  {errors?.professor_tutor_id && (
                    <FieldError>
                      {errors.professor_tutor_id?.message}
                    </FieldError>
                  )}
                </Field>

                <Field>
                  <FieldLabel>Alunos</FieldLabel>
                  <Controller
                    name="alunos"
                    control={control}
                    defaultValue={[]}
                    render={({ field }) => (
                      <MultipleSelect
                        placeholder="Selecione os alunos"
                        items={alunos.map((a) => ({
                          value: a.id,
                          label: a.nome,
                        }))}
                        onChange={(opts) =>
                          field.onChange(opts.map((o) => o.value))
                        }
                        value={field.value?.map((id) => ({
                          value: id,
                          label: alunos.find((a) => a.id === id)?.nome ?? id,
                        }))}
                      />
                    )}
                  />
                  {errors?.alunos && (
                    <FieldError>{errors.alunos?.message}</FieldError>
                  )}
                </Field>

                <Field>
                  <FieldLabel>Estudo de caso</FieldLabel>
                  <Textarea
                    disabled={isPending}
                    placeholder="Descreve o estudo de caso..."
                    {...register("estudo_caso")}
                  />
                </Field>

                {/*    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <Field>
                    <FieldLabel>Nota final</FieldLabel>
                    <Input
                      type="number"
                      min="0"
                      max="20"
                      disabled={isPending}
                      placeholder="Ex.: 15"
                      {...register("nota_final")}
                    />
                    {errors?.nota_final && <FieldError>{errors.nota_final?.message}</FieldError>}
                  </Field>

                  <Field>
                    <FieldLabel>Data de defesa</FieldLabel>
                    <Input
                      type="date"
                      disabled={isPending}
                      {...register("data_defesa")}
                    />
                  </Field>
                </div> */}

                <Field>
                  <Button type="submit" disabled={isPending}>
                    {isPending ? (
                      <>
                        <Loader2 className="animate-spin" /> A guardar...
                      </>
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
  );
}
