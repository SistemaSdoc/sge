"use client"

import { useEffect, useState } from "react"
import { Loader2 } from "lucide-react"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Field, FieldError, FieldGroup, FieldLabel, FieldSet } from "@/components/ui/field"
import { Select, SelectContent, SelectGroup, SelectLabel, SelectItem, SelectTrigger, SelectValue, } from "@/components/ui/select"
import { FormEventHandler } from "react"



interface InstituicaoFormData {
  nome: string;
  sigla: string;
  tipo: string;
  telefone: string;
  email: string;
  endereco: string;
  logo: File | null;
}

interface InstituicaoFormProps {
  title: string;
  data: InstituicaoFormData;
  setData: (key: keyof InstituicaoFormData, value: string | File | null) => void;
  errors: Partial<Record<keyof InstituicaoFormData, string>>;
  processing: boolean;
  submitFn: FormEventHandler;
  submitLabel?: string;
  logoUrl?: string | null;
}

export function InstituicaoForm({ title, data, setData, errors, processing, submitFn, submitLabel = 'Salvar', logoUrl }: InstituicaoFormProps) {
  const [previewUrl, setPreviewUrl] = useState<string | null>(null)

  useEffect(() => {
    if (data.logo instanceof File) {
      const url = URL.createObjectURL(data.logo)
      setPreviewUrl(url)

      return () => {
        URL.revokeObjectURL(url)
      }
    }

    setPreviewUrl(null)
    return undefined
  }, [data.logo])

  return (

    <div className="w-full max-w-sm px-6 py-6 mx-auto md:max-w-md lg:max-w-195">
      <form onSubmit={submitFn}>
        <Card className="overflow-visible">
          <CardHeader className="border-b">
            <CardTitle>{title}</CardTitle>
          </CardHeader>

          <CardContent>
            <FieldGroup>
              <FieldSet>
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <Field>
                    <FieldLabel htmlFor="nome">Nome</FieldLabel>
                    <Input
                      id="nome"
                      type="text"
                      placeholder="EX.: Instituição xyz"
                      value={data.nome}
                      onChange={(e) => setData("nome", e.target.value)}
                    />
                    {errors.nome && <FieldError>{errors.nome}</FieldError>}
                  </Field>

                  <Field>
                    <FieldLabel htmlFor="sigla">Sigla</FieldLabel>
                    <Input
                      id="sigla"
                      type="text"
                      placeholder="Ex.: IMCL"
                      value={data.sigla}
                      onChange={(e) => setData("sigla", e.target.value)}
                    />
                    {errors.sigla && <FieldError>{errors.sigla}</FieldError>}
                  </Field>
                </div>

                <Field>
                  <FieldLabel htmlFor="tipo">Tipo</FieldLabel>
                  <Select
                    value={data.tipo}
                    onValueChange={(value) => setData('tipo', value)}
                  >
                    <SelectTrigger className="w-full">
                      <SelectValue placeholder="Selecione o tipo de instituição" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectLabel>Tipo de instituição</SelectLabel>
                        <SelectItem value="instituto">Instituto</SelectItem>
                        <SelectItem value="colegio">Colégio</SelectItem>
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  {errors.tipo && <FieldError>{errors.tipo}</FieldError>}
                </Field>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 ">
                  <Field>
                    <FieldLabel htmlFor="telefone">Telefone</FieldLabel>
                    <div className="flex">
                      <span className="flex items-center justify-center px-3 py-1 text-xs border border-r-0 text-muted-foreground ">
                        +244
                      </span>

                      <Input
                        id="telefone"
                        type="tel"
                        placeholder="Ex.: 923000000"
                        value={data.telefone}
                        onChange={(e) => setData("telefone", e.target.value)}
                      />
                    </div>
                    {errors.telefone && <FieldError>{errors.telefone}</FieldError>}
                  </Field>

                  <Field>
                    <FieldLabel htmlFor="email">E-mail</FieldLabel>
                    <Input
                      id="email"
                      type="email"
                      value={data.email}
                      placeholder="EX.: email@exemplo.com"
                      onChange={(e) => setData("email", e.target.value)}
                    />
                    {errors.email && <FieldError>{errors.email}</FieldError>}
                  </Field>
                </div>

                <Field>
                  <FieldLabel htmlFor="endereco">Endereço</FieldLabel>
                  <Input
                    id="endereco"
                    type="text"
                    value={data.endereco}
                    placeholder="EX.: Rua de Luanda"
                    onChange={(e) => setData("endereco", e.target.value)}
                  />
                  {errors.endereco && <FieldError>{errors.endereco}</FieldError>}
                </Field>

                <Field>
                  <FieldLabel htmlFor="logo">Logo</FieldLabel>
                  <Input
                    id="logo"
                    type="file"
                    accept="image/jpeg,image/jpg,image/png,image/webp"
                    onChange={(e) => setData('logo', e.target.files?.[0] ?? null)}
                  />
                  {errors.logo && <FieldError>{errors.logo}</FieldError>}
                  {previewUrl ? (
                    <div className="mt-2">
                      <p className="text-xs text-muted-foreground">Preview do logo</p>
                      <img
                        src={previewUrl}
                        alt="Preview do logo"
                        className="mt-1 h-24 w-auto rounded border border-muted"
                      />
                    </div>
                  ) : logoUrl && !data.logo ? (
                    <div className="mt-2">
                      <p className="text-xs text-muted-foreground">Logo atual</p>
                      <img
                        src={logoUrl}
                        alt="Logo atual"
                        className="mt-1 h-24 w-auto rounded border border-muted"
                      />
                    </div>
                  ) : null}
                </Field>

                <Field>
                  <Button type="submit" disabled={processing}>
                    {processing ? (
                      <>
                        <Loader2 className="animate-spin" /> {submitLabel}...
                      </>
                    ) : (
                      <>{submitLabel}</>
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