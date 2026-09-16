import { Button } from '@/components/ui/button';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Clock3 } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldSet,
} from '@/components/ui/field';
import MultipleSelect from '@/components/multiple-select';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectLabel,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { useEffect, useState } from 'react';
import { cursoDetalhes as cursoDetalhesUrl } from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoController';

export function CursoForm({
  title,
  instituicao,
  classes,
  tenantsTutores,
  data,
  setData,
  errors,
  processing,
  onSubmit,
  niveisEnsino,
  tutelaPendente,
  herancaInicial = null,
  classesDetalhes = [],
  cursoId,
}) {
  const [herancaTutor, setHerancaTutor] = useState(herancaInicial);

  useEffect(() => {
    if (!data.tenant_tutor_id || !cursoId) return;

    const controller = new AbortController();

    fetch(
      `${cursoDetalhesUrl({ instituicao: instituicao.id }).url}?tenant_tutor_id=${encodeURIComponent(data.tenant_tutor_id)}&curso_id=${encodeURIComponent(cursoId)}`,
      { headers: { Accept: 'application/json' }, signal: controller.signal },
    )
      .then((r) => r.json())
      .then((payload) => {
        if (controller.signal.aborted) return;
        const d = payload.data;
        if (!d || !d.nivel_ensino_id) {
          setHerancaTutor(null);
          return;
        }
        setHerancaTutor(d);
        setData((prev) => ({
          ...prev,
          nivel_ensino_id: d.nivel_ensino_id,
          classes: d.classes.map((c) => c.id),
        }));
      })
      .catch((err) => {
        if (err.name === 'AbortError') return;
        setHerancaTutor(null);
      });

    return () => controller.abort();
  }, [data.tenant_tutor_id]);

  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md lg:max-w-195">
      <form onSubmit={onSubmit}>
        <Card className="overflow-visible">
          <CardHeader className="border-b">
            <CardTitle>{title}</CardTitle>
          </CardHeader>

          <CardContent>
            {tutelaPendente && (
              <Alert className="mb-4">
                <Clock3 />
                <AlertTitle>Troca de tutela pendente</AlertTitle>
                <AlertDescription>
                  Proposta para {tutelaPendente.tenant_tutor_nome}. A
                  instituição actual permanece activa até à conclusão das
                  aprovações.
                </AlertDescription>
              </Alert>
            )}
            <FieldGroup>
              <FieldSet>
                {instituicao.tipo === 'colegio' && (
                  <Field>
                    <FieldLabel htmlFor="tenant_tutor_id">
                      Instituição Tutora
                    </FieldLabel>
                    <Select
                      value={data.tenant_tutor_id || 'propria'}
                      onValueChange={(value) => {
                        setData((prev) => ({
                          ...prev,
                          tenant_tutor_id: value === 'propria' ? '' : value,
                          nivel_ensino_id: '',
                          classes: [],
                        }));
                        setHerancaTutor(null);
                      }}
                    >
                      <SelectTrigger className="w-full">
                        <SelectValue placeholder="Selecione a instituição tutora" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectGroup>
                          <SelectLabel>Tutela curricular</SelectLabel>
                          <SelectItem value="propria">
                            Tutela própria
                          </SelectItem>
                          {tenantsTutores?.map((tenant) => (
                            <SelectItem key={tenant.id} value={tenant.id}>
                              {tenant.nome}
                            </SelectItem>
                          ))}
                        </SelectGroup>
                      </SelectContent>
                    </Select>
                    {errors.tenant_tutor_id && (
                      <FieldError>{errors.tenant_tutor_id}</FieldError>
                    )}
                  </Field>
                )}

                {herancaTutor ? (
                  <Field>
                    <FieldLabel>Nível de Ensino e Classes</FieldLabel>
                    <div className="flex flex-wrap items-center gap-2">
                      <span className="border bg-muted px-3 py-1.5 text-sm font-medium">
                        {herancaTutor.nivel_ensino_nome}
                      </span>
                      <span className="text-muted-foreground">·</span>
                      {herancaTutor.classes.map((c) => (
                        <span
                          key={c.id}
                          className="border bg-muted px-3 py-1.5 text-sm"
                        >
                          {c.nome}
                        </span>
                      ))}
                      <span className="w-full text-xs text-muted-foreground">
                        Herdados do instituto tutor — não editáveis.
                      </span>
                    </div>
                  </Field>
                ) : (
                  <>
                    <Field>
                      <FieldLabel>Nível de Ensino</FieldLabel>
                      <Select
                        value={data.nivel_ensino_id}
                        onValueChange={(value) =>
                          setData('nivel_ensino_id', value)
                        }
                      >
                        <SelectTrigger className="w-full">
                          <SelectValue placeholder="Selecione o nível de ensino" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectGroup>
                            <SelectLabel>Níveis de Ensino</SelectLabel>
                            {niveisEnsino?.map((nivel) => (
                              <SelectItem key={nivel.id} value={nivel.id}>
                                {nivel.nome}
                              </SelectItem>
                            ))}
                          </SelectGroup>
                        </SelectContent>
                      </Select>
                      {errors.nivel_ensino_id && (
                        <FieldError>{errors.nivel_ensino_id}</FieldError>
                      )}
                    </Field>

                    <Field>
                      <FieldLabel htmlFor="classes">Classes</FieldLabel>
                      <MultipleSelect
                        placeholder="Selecione as classes"
                        items={classes?.map((classe) => ({
                          value: classe.id,
                          label: classe.nome,
                        }))}
                        onChange={(opts) =>
                          setData(
                            'classes',
                            opts.map((o) => String(o.value)),
                          )
                        }
                        value={(data.classes ?? []).map((id) => ({
                          value: id,
                          label:
                            classesDetalhes.find((c) => c.id === id)?.nome ??
                            classes?.find((c) => c.id === id)?.nome ??
                            id,
                        }))}
                      />
                      {errors.classes && (
                        <FieldError>{errors.classes}</FieldError>
                      )}
                    </Field>
                  </>
                )}

                <Field>
                  <Button type="submit" disabled={processing}>
                    Guardar
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