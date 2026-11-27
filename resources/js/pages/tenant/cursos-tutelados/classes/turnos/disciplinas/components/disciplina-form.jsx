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
import MultipleSelect from '@/components/multiple-select';
import { Spinner } from '@/components/spinner';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectLabel,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

export default function DisciplinaForm({
  params,
  disciplinas,
  disciplinaIds,
  setDisciplinaIds,
  errors,
  processing,
  anosLectivos,
  anoLectivoId,
  setAnoLectivoId,
}) {
  return (
    <div className="mx-auto w-full max-w-sm p-4 md:max-w-md md:p-6 lg:max-w-195">
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
        <div className="grid grid-cols-1 divide-y border-b bg-muted/50 text-center sm:grid-cols-2 sm:divide-x sm:divide-y-0 lg:grid-cols-3">
          <div className="min-w-0 px-3 py-3 sm:px-4 sm:py-4">
            <p className="text-sm font-bold wrap-break-word">
              {params.cursoTutelado.nome}
            </p>
            <p className="text-xs text-muted-foreground">Curso</p>
          </div>
          <div className="min-w-0 px-3 py-3 sm:px-4 sm:py-4">
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
              {/* ANO LECTIVO */}

              <Field>
                <FieldLabel>Ano Lectivo</FieldLabel>
                <Select
                  value={anoLectivoId ?? ''}
                  onValueChange={(value) => setAnoLectivoId(value || null)}
                >
                  <SelectTrigger className="w-full">
                    <SelectValue placeholder="Selecione ano lectivo" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      <SelectLabel>Anos lectivos</SelectLabel>
                      {anosLectivos?.map((a) => (
                        <SelectItem key={a.id} value={a.id}>
                          {a.nome}
                        </SelectItem>
                      ))}
                    </SelectGroup>
                  </SelectContent>
                </Select>
                {errors.ano_lectivo_id && (
                  <FieldError>{errors.ano_lectivo_id}</FieldError>
                )}
              </Field>

              {/* DISCIPLINAS */}
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
                  className="hover:cursor-pointer"
                >
                  {processing ? <Spinner className="size-4" /> : null}
                  Adicionar Disciplinas
                </Button>

                <Button
                  type="button"
                  variant="outline"
                  disabled={processing}
                  onClick={() => window.history.back()}
                  className="hover:cursor-pointer"
                >
                  <ArrowUpLeft /> Voltar
                </Button>
              </Field>
            </FieldSet>
          </FieldGroup>
        </CardContent>
      </Card>
    </div>
  );
}
