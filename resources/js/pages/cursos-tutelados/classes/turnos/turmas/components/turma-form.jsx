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
import { ArrowUpLeft, Loader } from 'lucide-react';

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
  can = {},
}) {
  const canSubmit = Boolean(can.create ?? can.update ?? true);

  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md lg:max-w-195">
      <form onSubmit={onSubmit}>
        <Card className="gap-0 overflow-visible">
          <CardHeader className="border-b">
            <CardTitle>{title}</CardTitle>
            <CardDescription>{description}</CardDescription>
          </CardHeader>

          {/* Cards de contexto */}
          <div className="grid grid-cols-3 divide-x border-b bg-muted/50 text-center">
            <div className="px-4 py-4">
              <p className="text-sm font-bold">{params.cursoTutelado.nome}</p>
              <p className="text-xs text-muted-foreground">Curso</p>
            </div>
            <div className="px-4 py-4">
              <p className="text-sm font-bold">{params.cursoClasse.nome}</p>
              <p className="text-xs text-muted-foreground">Classe</p>
            </div>
            <div className="px-4 py-4">
              <p className="text-sm font-bold">
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
                  <Button type="submit" disabled={processing || !canSubmit}>
                    {processing ? <Spinner className="size-4" /> : null}
                    {submitLabel}
                  </Button>

                  <Button
                    type="button"
                    variant={'outline'}
                    disabled={processing}
                    onClick={() => window.history.back()}
                  >
                    <ArrowUpLeft />
                    Voltar a classe
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
