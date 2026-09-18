import { ArrowUpLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldSet,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

export function DisciplinaForm({
  title,
  description,
  data,
  setData,
  errors,
  processing,
  onSubmit,
}) {
  return (
    <div className="mx-auto w-full max-w-2xl px-6 py-6">
      <form onSubmit={onSubmit}>
        <Card>
          <CardHeader className="border-b">
            <CardTitle>{title}</CardTitle>
            <CardDescription>{description}</CardDescription>
          </CardHeader>

          <CardContent>
            <FieldGroup>
              <FieldSet>
                <Field>
                  <FieldLabel htmlFor="nome">Nome da disciplina</FieldLabel>
                  <Input
                    id="nome"
                    value={data.nome}
                    onChange={(event) => setData('nome', event.target.value)}
                    placeholder="Ex.: Matemática"
                  />
                  {errors.nome && <FieldError>{errors.nome}</FieldError>}
                </Field>

                <div className="grid gap-4 md:grid-cols-2">
                  <Field>
                    <FieldLabel htmlFor="sigla">Sigla</FieldLabel>
                    <Input
                      id="sigla"
                      value={data.sigla}
                      onChange={(event) => setData('sigla', event.target.value)}
                      placeholder="Ex.: MAT"
                    />
                    {errors.sigla && <FieldError>{errors.sigla}</FieldError>}
                  </Field>

                  <Field>
                    <FieldLabel htmlFor="carga_horaria">
                      Carga horária
                    </FieldLabel>
                    <Input
                      id="carga_horaria"
                      type="number"
                      min="1"
                      value={data.carga_horaria}
                      onChange={(event) =>
                        setData('carga_horaria', event.target.value)
                      }
                    />
                    {errors.carga_horaria && (
                      <FieldError>{errors.carga_horaria}</FieldError>
                    )}
                  </Field>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                  <Field>
                    <FieldLabel>Componente</FieldLabel>
                    <Select
                      value={data.componente || 'none'}
                      onValueChange={(value) =>
                        setData('componente', value === 'none' ? '' : value)
                      }
                    >
                      <SelectTrigger className="w-full">
                        <SelectValue placeholder="Seleccione o componente" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectGroup>
                          <SelectLabel>Componente curricular</SelectLabel>
                          <SelectItem value="none">Não definido</SelectItem>
                          <SelectItem value="sociocultural">
                            Sociocultural
                          </SelectItem>
                          <SelectItem value="cientifica">Científica</SelectItem>
                          <SelectItem value="tecnica">Técnica</SelectItem>
                        </SelectGroup>
                      </SelectContent>
                    </Select>
                    {errors.componente && (
                      <FieldError>{errors.componente}</FieldError>
                    )}
                  </Field>

                  <Field>
                    <FieldLabel>Status</FieldLabel>
                    <Select
                      value={String(data.status)}
                      onValueChange={(value) =>
                        setData('status', Number(value))
                      }
                    >
                      <SelectTrigger className="w-full">
                        <SelectValue placeholder="Seleccione o status" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectGroup>
                          <SelectLabel>Status do catálogo</SelectLabel>
                          <SelectItem value="1">Activa</SelectItem>
                          <SelectItem value="0">Inactiva</SelectItem>
                        </SelectGroup>
                      </SelectContent>
                    </Select>
                    {errors.status && <FieldError>{errors.status}</FieldError>}
                  </Field>
                </div>

                <Field>
                  <Button type="submit" disabled={processing}>
                    Guardar disciplina
                  </Button>
                  <Button
                    type="button"
                    variant="outline"
                    disabled={processing}
                    onClick={() => window.history.back()}
                  >
                    <ArrowUpLeft />
                    Voltar
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
