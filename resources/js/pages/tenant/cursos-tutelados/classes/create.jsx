import { useForm } from '@inertiajs/react';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
  Field,
  FieldLabel,
  FieldError,
  FieldDescription,
  FieldGroup,
  FieldSet,
} from '@/components/ui/field';
import MultipleSelect from '@/components/multiple-select';
import { store } from '@/actions/App/Http/Controllers/Tenant/CursoClasseTurnoController';
import { ArrowUpLeft } from 'lucide-react';

export default function Create({
  instituicao,
  cursoTutelado,
  cursoClasse,
  turnos,
}) {
  const { data, setData, put, processing, errors } = useForm({
    turnos: [],
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    put(
      store({
        instituicao: instituicao.id,
        cursoTutelado: cursoTutelado.id,
        cursoClasse: cursoClasse.id,
      }).url,
      {
        preserveScroll: true,
      },
    );
  };

  return (
    <div className="mx-auto w-full max-w-sm p-4 md:max-w-md md:p-6 lg:max-w-195">
      <form onSubmit={handleSubmit}>
        <Card className="gap-0 overflow-visible">
          <CardHeader className="border-b">
            <CardTitle>Definir Turnos</CardTitle>
            <CardDescription>
              Defina quais turnos esta classe estará disponível
            </CardDescription>
          </CardHeader>

          {/* Cards de contexto */}
          <div className="grid grid-cols-1 divide-y border-b bg-muted/50 text-center sm:grid-cols-2 sm:divide-x sm:divide-y-0">
            <div className="min-w-0 px-3 py-3 sm:px-4 sm:py-4">
              <p className="text-sm font-bold wrap-break-word">
                {cursoTutelado.nome}
              </p>
              <p className="text-xs text-muted-foreground">Curso</p>
            </div>
            <div className="min-w-0 px-3 py-3 sm:px-4 sm:py-4">
              <p className="text-sm font-bold wrap-break-word">
                {cursoClasse.nome}
              </p>
              <p className="text-xs text-muted-foreground">Classe</p>
            </div>
          </div>

          {/* Form */}
          <CardContent className="pt-6">
            <FieldGroup>
              <FieldSet>
                {/* Turnos */}
                <Field>
                  <FieldLabel>Turnos</FieldLabel>
                  <FieldDescription>
                    Seleccione os turnos em que esta classe estará disponível.
                  </FieldDescription>
                  <MultipleSelect
                    placeholder="Selecione os turnos"
                    items={turnos?.map((t) => ({ value: t.id, label: t.nome }))}
                    onChange={(opts) =>
                      setData(
                        'turnos',
                        opts.map((o) => o.value),
                      )
                    }
                    value={data.turnos.map((id) => ({
                      value: id,
                      label: turnos?.find((t) => t.id === id)?.nome ?? id,
                    }))}
                    disabled={processing}
                  />
                  {errors.turnos && <FieldError>{errors.turnos}</FieldError>}
                </Field>

                {/* Botões de acção */}
                <Field orientation={'vertical'}>
                  <Button
                    type="submit"
                    disabled={processing || data.turnos.length === 0}
                    className="hover:cursor-pointer"
                  >
                    Definir Turnos
                  </Button>

                  <Button
                    variant="outline"
                    type="button"
                    disabled={processing}
                    onClick={() => window.history.back()}
                    className="hover:cursor-pointer"
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
