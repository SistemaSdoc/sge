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
import { Textarea } from '@/components/ui/textarea';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { ArrowUpLeft } from 'lucide-react';

export function CursoForm({
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
                  <FieldLabel htmlFor="nome">Nome do curso</FieldLabel>
                  <Input
                    id="nome"
                    value={data.nome}
                    onChange={(event) => setData('nome', event.target.value)}
                    placeholder="Ex.: Informática de Gestão"
                  />
                  {errors.nome && <FieldError>{errors.nome}</FieldError>}
                </Field>

                <Field>
                  <FieldLabel htmlFor="descricao">Descrição</FieldLabel>
                  <Textarea
                    id="descricao"
                    value={data.descricao}
                    onChange={(event) =>
                      setData('descricao', event.target.value)
                    }
                    placeholder="Descrição opcional do curso"
                  />
                  {errors.descricao && (
                    <FieldError>{errors.descricao}</FieldError>
                  )}
                </Field>

                <div className="grid gap-4 md:grid-cols-2">
                  <Field>
                    <FieldLabel htmlFor="duracao_anos">
                      Duração (anos)
                    </FieldLabel>
                    <Input
                      id="duracao_anos"
                      type="number"
                      min="1"
                      max="10"
                      value={data.duracao_anos}
                      onChange={(event) =>
                        setData('duracao_anos', event.target.value)
                      }
                    />
                    {errors.duracao_anos && (
                      <FieldError>{errors.duracao_anos}</FieldError>
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
                          <SelectItem value="1">Activo</SelectItem>
                          <SelectItem value="0">Inactivo</SelectItem>
                        </SelectGroup>
                      </SelectContent>
                    </Select>
                    {errors.status && <FieldError>{errors.status}</FieldError>}
                  </Field>
                </div>

                <Field>
                  <Button type="submit" disabled={processing}>
                    Guardar curso
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
