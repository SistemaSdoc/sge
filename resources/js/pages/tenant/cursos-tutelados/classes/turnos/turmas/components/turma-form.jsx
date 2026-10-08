import { Spinner } from '@/components/spinner';
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
import { ArrowUpLeft } from 'lucide-react';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

export function TurmaForm({
  title,
  description,
  submitLabel,
  params,
  data,
  setData,
  errors,
  processing,
  onSubmit,
  anosLectivos = [],
  can = {},
}) {
  const canSubmit = Boolean(can.create ?? can.update ?? true);

  return (
    <div className="mx-auto w-full max-w-sm p-4 md:max-w-md md:p-6 lg:max-w-195">
      <form onSubmit={onSubmit}>
        <Card className="gap-0 overflow-visible">
          <CardHeader className="border-b">
            <CardTitle>{title}</CardTitle>
            <CardDescription>{description}</CardDescription>
          </CardHeader>

          {/* Cards de contexto */}
          <div className="grid grid-cols-1 divide-y border-b bg-muted/50 text-center sm:grid-cols-2 sm:divide-x sm:divide-y-0 lg:grid-cols-3">
            <div className="min-w-0 px-3 py-3 sm:px-4 sm:py-4">
              <p className="text-sm font-bold wrap-break-word">
                {params.cursoTutelado.nome}
              </p>
              <p className="text-xs text-muted-foreground">Curso</p>
            </div>
            <div className="min-w-0 bg-muted/90 px-3 py-3 sm:px-4 sm:py-4">
              <p className="text-sm font-bold wrap-break-word">
                {params.cursoClasse.nome}
              </p>
              <p className="text-xs text-muted-foreground">Classe</p>
            </div>
            <div className="min-w-0 px-3 py-3 sm:px-4 sm:py-4">
              <p className="text-sm font-bold wrap-break-word">
                {params.cursoClasseTurno.nome}
              </p>
              <p className="text-xs text-muted-foreground">Classe</p>
            </div>
          </div>

          <CardContent className="pt-6">
            <FieldGroup>
              <FieldSet>
                <Field>
                  <FieldLabel htmlFor="nome">Nome</FieldLabel>
                  <Input
                    id="nome"
                    type="text"
                    placeholder="Ex.: Turma A"
                    value={data.nome}
                    onChange={(e) => setData('nome', e.target.value)}
                  />
                  {errors.nome && <FieldError>{errors.nome}</FieldError>}
                </Field>

                <Field>
                  <FieldLabel htmlFor="max_alunos">Máximo de alunos</FieldLabel>
                  <Input
                    id="max_alunos"
                    type="number"
                    placeholder="Ex.: 30"
                    value={data.max_alunos}
                    onChange={(e) => setData('max_alunos', e.target.value)}
                  />
                  {errors.max_alunos && (
                    <FieldError>{errors.max_alunos}</FieldError>
                  )}
                </Field>

                <Field>
                  <FieldLabel htmlFor="ano_lectivo_id">Ano Lectivo</FieldLabel>
                  <Select
                    id="ano_lectivo_id"
                    value={data.ano_lectivo_id ?? ''}
                    onValueChange={(value) =>
                      setData('ano_lectivo_id', value || null)
                    }
                  >
                    <SelectTrigger className="w-full">
                      <SelectValue placeholder="Selecione ano lectivo" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectLabel>Anos lectivos</SelectLabel>
                        {anosLectivos.map((anoLectivo) => (
                          <SelectItem key={anoLectivo.id} value={anoLectivo.id}>
                            {anoLectivo.nome}
                          </SelectItem>
                        ))}
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  {errors.ano_lectivo_id && (
                    <FieldError>{errors.ano_lectivo_id}</FieldError>
                  )}
                </Field>

                <Field>
                  <Button
                    type="submit"
                    disabled={processing || !canSubmit || !data.nome}
                    className="hover:cursor-pointer"
                  >
                    {processing ? <Spinner className="size-4" /> : null}
                    {submitLabel}
                  </Button>

                  <Button
                    type="button"
                    variant={'outline'}
                    disabled={processing}
                    onClick={() => window.history.back()}
                    className="hover:cursor-pointer"
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
