import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Field, FieldError, FieldGroup, FieldLabel, FieldSet } from '@/components/ui/field';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';

export default function ProfessorForm({
  professores,
  professorId,
  setProfessorId,
  tipo,
  setTipo,
  coordenador,
  setCoordenador,
  errors,
  processing,
}) {
  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md lg:max-w-195">
      <Card className="overflow-visible">
        <CardHeader className="border-b">
          <div className="flex items-center justify-between">
            <CardTitle>Associar Professor</CardTitle>

            <Field orientation="horizontal" className="w-fit">
              <FieldLabel htmlFor="coordenador">Coordenador</FieldLabel>
              <Switch
                size="sm"
                id="coordenador"
                checked={coordenador}
                onCheckedChange={setCoordenador}
              />
            </Field>
          </div>
        </CardHeader>

        <CardContent>
          <FieldGroup>
            <FieldSet>
              <Field>
                <FieldLabel htmlFor="professor_id">Professor</FieldLabel>
                <Select value={professorId} onValueChange={setProfessorId}>
                  <SelectTrigger className="w-full">
                    <SelectValue placeholder="Selecione o professor" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      <SelectLabel>Professores</SelectLabel>
                      {professores.map((p) => (
                        <SelectItem key={p.id} value={String(p.id)}>
                          {p.user?.nome || `Professor ${p.id}`}
                        </SelectItem>
                      ))}
                    </SelectGroup>
                  </SelectContent>
                </Select>
                {errors?.professor_id && <FieldError>{errors.professor_id}</FieldError>}
              </Field>

              <Field>
                <FieldLabel htmlFor="tipo">Tipo</FieldLabel>
                <Select value={tipo} onValueChange={setTipo}>
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
                {errors?.tipo && <FieldError>{errors.tipo}</FieldError>}
              </Field>

              <Field>
                <Button type="submit" disabled={processing}>
                  {processing ? (
                    <>
                      <Loader2 className="animate-spin" /> A guardar...
                    </>
                  ) : (
                    <>Associar</>
                  )}
                </Button>
              </Field>
            </FieldSet>
          </FieldGroup>
        </CardContent>
      </Card>
    </div>
  );
}
