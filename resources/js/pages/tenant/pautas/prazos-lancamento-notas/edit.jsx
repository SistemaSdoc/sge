import { useEffect, useMemo, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { update } from '@/actions/App/Http/Controllers/Tenant/PeriodoLancamentoNotasController';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardDescription,
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
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

const PERIODOS = [
  { value: '1', label: '1º Trimestre' },
  { value: '2', label: '2º Trimestre' },
  { value: '3', label: '3º Trimestre' },
];

function buildPeriodosMap(periodos = []) {
  return Object.fromEntries(
    periodos.map((periodo) => [
      String(periodo.periodo),
      {
        data_inicio: periodo.data_inicio ?? '',
        data_limite: periodo.data_limite ?? '',
      },
    ]),
  );
}

function getPeriodoInicial(periodos = []) {
  const periodoNaoDefinido = periodos.find(
    (periodo) => !periodo.data_inicio || !periodo.data_limite,
  );

  return String(periodoNaoDefinido?.periodo ?? 1);
}

function getPeriodosLiberados(periodos = []) {
  const periodosOrdenados = [...periodos].sort((a, b) => a.periodo - b.periodo);
  const liberados = { 1: true };
  let anteriorConfigurado = true;

  for (const periodo of periodosOrdenados) {
    if (periodo.periodo === 1) {
      anteriorConfigurado = Boolean(periodo.data_inicio && periodo.data_limite);
      continue;
    }

    liberados[periodo.periodo] = anteriorConfigurado;
    anteriorConfigurado =
      anteriorConfigurado &&
      Boolean(periodo.data_inicio && periodo.data_limite);
  }

  liberados[1] = true;

  return liberados;
}

function PeriodoFields({ periodo, data, setData, errors }) {
  return (
    <FieldSet >
      <div className="mb-4 flex items-center justify-between">
        <h3 className="text-sm font-semibold text-foreground">
          {periodo.periodo}º Trimestre
        </h3>

        <span className="text-xs text-muted-foreground">
          Período de lançamento
        </span>
      </div>

      <FieldGroup className="grid gap-4 md:grid-cols-2">
        <Field>
          <FieldLabel htmlFor="data_inicio">Data de início</FieldLabel>
          <Input
            id="data_inicio"
            type="datetime-local"
            value={data.data_inicio ?? ''}
            onChange={(e) => setData('data_inicio', e.target.value)}
          />
          {errors?.data_inicio && <FieldError>{errors.data_inicio}</FieldError>}
        </Field>

        <Field>
          <FieldLabel htmlFor="data_limite">Data limite</FieldLabel>
          <Input
            id="data_limite"
            type="datetime-local"
            value={data.data_limite ?? ''}
            onChange={(e) => setData('data_limite', e.target.value)}
          />
          {errors?.data_limite && <FieldError>{errors.data_limite}</FieldError>}
        </Field>
      </FieldGroup>
    </FieldSet>
  );
}

export default function Edit({
  instituicao,
  anoLectivo,
  periodos = [],
  periodoInicial = 1,
}) {
  const periodosMap = useMemo(() => buildPeriodosMap(periodos), [periodos]);
  const periodosLiberados = useMemo(
    () => getPeriodosLiberados(periodos),
    [periodos],
  );
  const [periodoSelecionado, setPeriodoSelecionado] = useState(
    getPeriodoInicial(periodos) ?? String(periodoInicial),
  );

  const { data, setData, put, processing, errors } = useForm({
    periodo: Number(periodoInicial),
    data_inicio: periodosMap[String(periodoInicial)]?.data_inicio ?? '',
    data_limite: periodosMap[String(periodoInicial)]?.data_limite ?? '',
  });

  useEffect(() => {
    const periodoAtual = periodosMap[periodoSelecionado] ?? {
      data_inicio: '',
      data_limite: '',
    };

    setData('periodo', Number(periodoSelecionado));
    setData('data_inicio', periodoAtual.data_inicio ?? '');
    setData('data_limite', periodoAtual.data_limite ?? '');
  }, [periodoSelecionado, periodosMap, setData]);

  const submitFn = (e) => {
    e.preventDefault();
    put(update(instituicao.id).url, {
      preserveScroll: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-4xl px-6 py-6">
      <Head title="Prazos de lançamento de notas" />

      <form onSubmit={submitFn}>
        <Card className="gap-0">
          <CardHeader className="border-b">
            <CardTitle>Prazos de lançamento de notas</CardTitle>
            <CardDescription>
              Define o prazo de lançamento por trimestre para {anoLectivo.nome}.
            </CardDescription>
          </CardHeader>

          <CardContent className="space-y-4 pt-6">
            <Field>
              <FieldLabel htmlFor="periodo">Trimestre</FieldLabel>
              <Select
                value={periodoSelecionado}
                onValueChange={setPeriodoSelecionado}
              >
                <SelectTrigger id="periodo">
                  <SelectValue placeholder="Seleccionar trimestre" />
                </SelectTrigger>

                <SelectContent>
                  {PERIODOS.map((periodo) => (
                    <SelectItem
                      key={periodo.value}
                      value={periodo.value}
                      disabled={
                        !periodosLiberados[Number(periodo.value)] &&
                        periodo.value !== periodoSelecionado
                      }
                    >
                      {periodo.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {errors?.periodo && <FieldError>{errors.periodo}</FieldError>}
            </Field>

            <PeriodoFields
              periodo={{ periodo: periodoSelecionado }}
              data={{
                data_inicio: data.data_inicio,
                data_limite: data.data_limite,
              }}
              setData={setData}
              errors={errors}
            />

            <Button type="submit" className="w-full" disabled={processing}>
              {processing ? 'A guardar...' : 'Guardar prazo'}
            </Button>
          </CardContent>
        </Card>
      </form>
    </div>
  );
}
