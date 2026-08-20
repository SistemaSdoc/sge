import { ArrowUpLeft, Loader2 } from 'lucide-react';
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
import MultipleSelect from '@/components/multiple-select';
import { Spinner } from '@/components/spinner';

export default function DisciplinaForm({
  params,
  disciplinas,
  disciplinaIds,
  setDisciplinaIds,
  errors,
  processing,
}) {
  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md lg:max-w-195">
      <Card className="gap-0 overflow-visible">
        <CardHeader className="border-b">
          <CardTitle>Adicionar Disciplinas</CardTitle>
          <CardDescription>
            Adicione as disciplinas que o turno da{' '}
            <span className="font-bold">{params.cursoClasseTurno.nome}</span> da{' '}
            <span className="font-bold">{params.cursoClasse.nome} classe</span>{' '}
            terá
          </CardDescription>
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
            <p className="text-sm font-bold">{params.cursoClasseTurno.nome}</p>
            <p className="text-xs text-muted-foreground">Classe</p>
          </div>
        </div>

        <CardContent className="pt-6">
          <FieldGroup>
            <FieldSet>
              <Field>
                <FieldLabel>Disciplinas</FieldLabel>

                <MultipleSelect
                  placeholder="Selecione as disciplinas"
                  items={disciplinas.map((d) => ({
                    value: d.id,
                    label: d.nome,
                  }))}
                  onChange={(opts) =>
                    setDisciplinaIds(opts.map((o) => o.value))
                  }
                  value={disciplinaIds.map((id) => ({
                    value: id,
                    label: disciplinas.find((d) => d.id === id)?.nome ?? id,
                  }))}
                />

                {errors.disciplina_ids && (
                  <FieldError>{errors.disciplina_ids}</FieldError>
                )}
              </Field>

              <Field>
                <Button
                  type="submit"
                  disabled={processing || disciplinaIds.length === 0}
                >
                  {processing ? <Spinner className="size-4" /> : null}
                  Adicionar Disciplinas
                </Button>

                <Button
                  type="button"
                  variant={'outline'}
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
    </div>
  );
}
