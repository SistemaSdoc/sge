import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldSet,
} from '@/components/ui/field';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';

export default function ProfessorForm({
  // modo
  mode = 'create', // 'create' | 'edit'
  // create
  professores = [],
  professorId,
  setProfessorId,
  // edit
  professorNome,
  // partilhado
  tipo,
  setTipo,
  coordenador,
  setCoordenador,
  opap,
  setOpap,
  errors,
  processing,
}) {
  const hasAvailableProfessores = mode !== 'create' || professores.length > 0;

  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md lg:max-w-195">
      <Card className="overflow-visible">
        <CardHeader className="border-b">
          <div className="flex items-center justify-between">
            <CardTitle>
              {mode === 'create' ? 'Associar Professor' : 'Editar Professor'}
            </CardTitle>

            <div className="flex items-center gap-5">
              <Field orientation="horizontal" className="flex w-fit">
                <FieldLabel htmlFor="coordenador">Coordenador</FieldLabel>
                <Switch
                  size="sm"
                  id="coordenador"
                  checked={coordenador}
                  onCheckedChange={setCoordenador}
                />
              </Field>

              <Field orientation="horizontal" className="flex w-fit">
                <FieldLabel htmlFor="opap">OPAP</FieldLabel>
                <Switch
                  size="sm"
                  id="opap"
                  checked={opap}
                  onCheckedChange={setOpap}
                />
              </Field>
            </div>
          </div>
        </CardHeader>

        <CardContent>
          <FieldGroup>
            <FieldSet>
              <Field>
                <FieldLabel htmlFor="professor_id">Professor</FieldLabel>
                {mode === 'create' ? (
                  <Select
                    value={professorId}
                    onValueChange={setProfessorId}
                    disabled={!hasAvailableProfessores}
                  >
                    <SelectTrigger className="w-full">
                      <SelectValue
                        placeholder={
                          hasAvailableProfessores
                            ? 'Selecione o professor'
                            : 'Não há professores disponíveis'
                        }
                      />
                    </SelectTrigger>
                    {hasAvailableProfessores && (
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
                    )}
                  </Select>
                ) : (
                  <p className="text-sm text-muted-foreground">
                    {professorNome}
                  </p>
                )}
                {errors?.professor_id && (
                  <FieldError>{errors.professor_id}</FieldError>
                )}
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
                <Button
                  type="submit"
                  disabled={
                    processing ||
                    (mode === 'create' &&
                      (!hasAvailableProfessores || !professorId))
                  }
                >
                  {processing ? (
                    <>
                      <Loader2 className="animate-spin" /> A guardar...
                    </>
                  ) : mode === 'create' ? (
                    'Associar'
                  ) : (
                    'Guardar'
                  )}
                </Button>
              </Field>

              {errors?.coordenador && (
                <FieldError>{errors.coordenador}</FieldError>
              )}
            </FieldSet>
          </FieldGroup>
        </CardContent>
      </Card>
    </div>
  );
}
