import { useForm } from '@inertiajs/react';
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
  FieldDescription,
} from '@/components/ui/field';
import MultipleSelect from '@/components/multiple-select';
import { update } from '@/actions/App/Http/Controllers/Tenant/CursoClasseTurnoController';
import { Spinner } from '@/components/spinner';

export default function EditTurno({
  instituicao,
  cursoTutelado,
  cursoClasse,
  turnos,
  turnosSelecionados,
}) {
  const { data, setData, patch, processing, errors } = useForm({
    turnos: turnosSelecionados,
  });

  const handleSubmit = (event) => {
    event.preventDefault();

    patch(
      update({
        instituicao: instituicao.id,
        cursoTutelado: cursoTutelado.id,
        cursoClasse: cursoClasse.id,
      }).url,
    );
  };

  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md lg:max-w-195">
      <form onSubmit={handleSubmit}>
        <Card className="gap-0 overflow-visible">
          <CardHeader className="border-b">
            <CardTitle>Editar Turnos</CardTitle>
            <CardDescription>
              Actualize os turnos associados à classe {cursoClasse.nome}.
            </CardDescription>
          </CardHeader>

          <div className="grid grid-cols-2 divide-x border-b bg-muted/50 text-center">
            <div className="px-4 py-4">
              <p className="text-sm font-bold">{cursoTutelado.nome}</p>
              <p className="text-xs text-muted-foreground">Curso</p>
            </div>
            <div className="px-4 py-4">
              <p className="text-sm font-bold">{cursoClasse.nome}</p>
              <p className="text-xs text-muted-foreground">Classe</p>
            </div>
          </div>

          <CardContent className="pt-6">
            <FieldGroup>
              <FieldSet>
                <Field>
                  <FieldLabel>Turnos da classe</FieldLabel>
                  <FieldDescription>
                    Adicione ou remova os turnos disponíveis para esta classe.
                  </FieldDescription>
                  <MultipleSelect
                    placeholder="Selecione os turnos"
                    items={turnos.map((turno) => ({
                      value: turno.id,
                      label: turno.nome,
                    }))}
                    value={turnos
                      .filter((turno) => data.turnos.includes(turno.id))
                      .map((turno) => ({
                        value: turno.id,
                        label: turno.nome,
                      }))}
                    onChange={(options) =>
                      setData(
                        'turnos',
                        options.map((option) => option.value),
                      )
                    }
                    disabled={processing}
                  />
                  {errors.turnos && <FieldError>{errors.turnos}</FieldError>}
                </Field>

                <Field orientation="vertical">
                  <Button type="submit" disabled={processing}>
                    {processing ? <Spinner className="size-4" /> : null}
                    Guardar alterações
                  </Button>
                  <Button
                    type="button"
                    variant="outline"
                    disabled={processing}
                    onClick={() => window.history.back()}
                  >
                    <ArrowUpLeft />
                    Voltar à classe
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
