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
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md lg:max-w-195">
      <form onSubmit={handleSubmit}>
        <Card className="gap-0 overflow-visible">
          <CardHeader className="border-b">
            <CardTitle>Definir Turnos</CardTitle>
            <CardDescription>
              Defina quais turnos esta classe estará disponível
            </CardDescription>
          </CardHeader>

          {/* Cards de contexto */}
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

          {/* Form */}
          <CardContent className="pt-6">
            <FieldGroup>
              <FieldSet>
                {/* Turnos */}
                <Field>
                  <FieldLabel>Turnos</FieldLabel>
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
                  >
                    Definir Turnos
                  </Button>

                  <Button
                    variant="outline"
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
