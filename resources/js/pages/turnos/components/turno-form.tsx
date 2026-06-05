import { FormEventHandler } from "react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Field, FieldError, FieldGroup, FieldLabel, FieldSet } from "@/components/ui/field";

interface TurnoFormData {
  nome: string;
}

interface TurnoFormProps {
  title: string;
  data: TurnoFormData;
  setData: (key: keyof TurnoFormData, value: string) => void;
  errors: Partial<Record<keyof TurnoFormData, string>>;
  processing: boolean;
  submitFn: FormEventHandler;
}

export function TurnoForm({ title, data, setData, errors, processing, submitFn }: TurnoFormProps) {
  return (
    <div className="w-full max-w-sm px-6 py-6 mx-auto md:max-w-md lg:max-w-2xl">
      <form onSubmit={submitFn}>
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
                    placeholder="Ex.: Manhã"
                    value={data.nome}
                    onChange={(e) => setData("nome", e.target.value)}
                  />
                  {errors.nome && <FieldError>{errors.nome}</FieldError>}
                </Field>

                <Field>
                  <Button type="submit" disabled={processing}>
                    {processing ? "A guardar..." : "Adicionar"}
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