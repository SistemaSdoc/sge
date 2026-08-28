import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldSet,
} from '@/components/ui/field';
import MultipleSelect from '@/components/multiple-select';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectLabel,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

export function CursoForm({
  title,
  instituicao,
  classes,
  tenantsTutores,
  data,
  setData,
  errors,
  processing,
  onSubmit,
}) {
  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md lg:max-w-195">
      <form onSubmit={onSubmit}>
        <Card className="overflow-visible">
          <CardHeader className="border-b">
            <CardTitle>{title}</CardTitle>
          </CardHeader>

          <CardContent>
            <FieldGroup>
              <FieldSet>
                {instituicao.tipo === 'colegio' && (
                  <Field>
                    <FieldLabel htmlFor="tenant_tutor_id">
                      Instituição Tutora
                    </FieldLabel>
                    <Select
                      value={data.tenant_tutor_id || 'propria'}
                      onValueChange={(value) =>
                        setData(
                          'tenant_tutor_id',
                          value === 'propria' ? '' : value,
                        )
                      }
                    >
                      <SelectTrigger className="w-full">
                        <SelectValue placeholder="Selecione a instituição tutora" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectGroup>
                          <SelectLabel>Tutela curricular</SelectLabel>
                          <SelectItem value="propria">
                            Tutela própria
                          </SelectItem>
                          {tenantsTutores?.map((tenant) => (
                            <SelectItem key={tenant.id} value={tenant.id}>
                              {tenant.nome}
                            </SelectItem>
                          ))}
                        </SelectGroup>
                      </SelectContent>
                    </Select>
                    {errors.tenant_tutor_id && (
                      <FieldError>{errors.tenant_tutor_id}</FieldError>
                    )}
                  </Field>
                )}

                <Field>
                  <FieldLabel htmlFor="classes">Classes</FieldLabel>
                  <MultipleSelect
                    placeholder="Selecione as classes"
                    items={classes?.map((classe) => ({
                      value: classe.id,
                      label: classe.nome,
                    }))}
                    onChange={(opts) =>
                      setData(
                        'classes',
                        opts.map((o) => o.value),
                      )
                    }
                    value={data.classes.map((id) => ({
                      value: id,
                      label: classes?.find((c) => c.id === id)?.nome ?? id,
                    }))}
                  />
                  {errors.classes && <FieldError>{errors.classes}</FieldError>}
                </Field>

                <Field>
                  <FieldLabel htmlFor="duracao_anos">Duração (anos)</FieldLabel>
                  <Input
                    id="duracao_anos"
                    type="number"
                    placeholder="Ex.: 3"
                    value={data.duracao_anos}
                    onChange={(e) => setData('duracao_anos', e.target.value)}
                  />
                  {errors.duracao_anos && (
                    <FieldError>{errors.duracao_anos}</FieldError>
                  )}
                </Field>
                <Field>
                  <Button type="submit" disabled={processing}>
                    Guardar
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
