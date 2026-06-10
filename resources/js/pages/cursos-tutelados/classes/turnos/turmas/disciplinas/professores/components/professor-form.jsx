import { Loader2 } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Field, FieldError, FieldGroup, FieldLabel, FieldSet } from "@/components/ui/field"
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from "@/components/ui/select"

export default function ProfessorForm({
  disciplinas,
  professores,
  data,
  setData,
  errors,
  processing,
}) {
  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md lg:max-w-195">
      <Card className="overflow-visible">
        <CardHeader className="border-b">
          <CardTitle>Definir Professor</CardTitle>
        </CardHeader>

        <CardContent>
          <FieldGroup>
            <FieldSet>
              <Field>
                <FieldLabel>Disciplina</FieldLabel>
                <Select
                  name="disciplina_id"
                  value={data?.disciplina_id ?? ""}
                  onValueChange={(value) => setData("disciplina_id", value)}
                >
                  <SelectTrigger className="w-full">
                    <SelectValue placeholder="Selecione a disciplina" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      <SelectLabel>Disciplinas</SelectLabel>
                      {disciplinas.map((disciplina) => (
                        <SelectItem key={disciplina.id} value={disciplina.id}>
                          {disciplina?.disciplina?.nome ?? disciplina?.nome ?? "Sem nome"}
                        </SelectItem>
                      ))}
                    </SelectGroup>
                  </SelectContent>
                </Select>
                {errors.disciplina_id && <FieldError>{errors.disciplina_id}</FieldError>}
              </Field>

              <Field>
                <FieldLabel>Professor</FieldLabel>
                <Select
                  name="professor_id"
                  value={data?.professor_id ?? ""}
                  onValueChange={(value) => setData("professor_id", value)}
                >
                  <SelectTrigger className="w-full">
                    <SelectValue placeholder="Selecione o professor" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      <SelectLabel>Professores do curso</SelectLabel>
                      {professores.map((professor) => (
                        <SelectItem key={professor.id} value={professor.id}>
                          {professor.nome}
                        </SelectItem>
                      ))}
                    </SelectGroup>
                  </SelectContent>
                </Select>
                {errors.professor_id && <FieldError>{errors.professor_id}</FieldError>}
              </Field>

              <Field>
                <Button type="submit" disabled={processing}>
                  {processing ? <Loader2 className="animate-spin" /> : "Adicionar"}
                </Button>
              </Field>
            </FieldSet>
          </FieldGroup>
        </CardContent>
      </Card>
    </div>
  )
}
