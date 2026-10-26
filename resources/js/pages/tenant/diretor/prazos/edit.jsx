import { Head, useForm, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldSet,
} from '@/components/ui/field';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/spinner';

export default function Edit({ prazo, disciplinas, classes }) {
  const { data, setData, put, processing, errors } = useForm({
    titulo: prazo.titulo || '',
    tipo_prova: prazo.tipo_prova,
    disciplina_id: prazo.disciplina_id || '',
    classe_id: prazo.classe_id || '',
    data_inicio: prazo.data_inicio,
    data_limite: prazo.data_limite,
    periodo: prazo.periodo,
    observacoes: prazo.observacoes || '',
    permite_reenvio: prazo.permite_reenvio,
  });

  const submit = (e) => {
    e.preventDefault();
    put(`/dashboard/diretor/prazos/${prazo.id}`);
  };

  return (
    <>
      <Head title={`Editar: ${prazo.titulo}`} />

      <div className="mx-auto w-full max-w-4xl space-y-4 p-4 sm:p-6">
        <Card className="gap-0 pb-0">
          <CardHeader className="border-b border-foreground/10">
            <div className="min-w-0 space-y-1">
              <Breadcrumb>
                <BreadcrumbList className="flex-wrap">
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbLink asChild>
                      <Link
                        href="/dashboard/diretor/prazos"
                        className="line-clamp-2 text-sm font-semibold text-primary"
                      >
                        Prazos
                      </Link>
                    </BreadcrumbLink>
                  </BreadcrumbItem>
                  <BreadcrumbSeparator />
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbPage className="line-clamp-2 text-sm font-semibold text-secondary">
                      Editar
                    </BreadcrumbPage>
                  </BreadcrumbItem>
                </BreadcrumbList>
              </Breadcrumb>
              <CardDescription>
                Atualize as informações do prazo de submissão.
              </CardDescription>
            </div>
            <CardAction>
              <Button variant="outline" size="sm" asChild>
                <Link href={`/dashboard/diretor/prazos/${prazo.id}`}>
                  <ArrowLeft className="mr-1.5 size-4" />
                  Voltar
                </Link>
              </Button>
            </CardAction>
          </CardHeader>

          <CardContent className="p-4 sm:p-6">
            <form onSubmit={submit}>
              <FieldGroup>
                <FieldSet>
                  {/* Título */}
                  <Field>
                    <FieldLabel htmlFor="titulo">
                      Título{' '}
                      <span className="text-xs font-normal text-muted-foreground">
                        (opcional)
                      </span>
                    </FieldLabel>
                    <Input
                      id="titulo"
                      value={data.titulo}
                      onChange={(e) => setData('titulo', e.target.value)}
                      placeholder="Ex: Exame Final 1º Trimestre"
                      disabled={processing}
                    />
                    {errors.titulo && <FieldError>{errors.titulo}</FieldError>}
                  </Field>

                  <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {/* Tipo de Prova */}
                    <Field>
                      <FieldLabel htmlFor="tipo_prova">Tipo de Prova *</FieldLabel>
                      <Select
                        value={data.tipo_prova}
                        onValueChange={(value) => setData('tipo_prova', value)}
                        disabled={processing}
                      >
                        <SelectTrigger id="tipo_prova" className="w-full">
                          <SelectValue placeholder="Selecione o tipo" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="Prova-Trimestral">
                            Prova Trimestral
                          </SelectItem>
                          <SelectItem value="Exame-especial">
                            Exame Especial
                          </SelectItem>
                          <SelectItem value="Recurso">Recurso</SelectItem>
                        </SelectContent>
                      </Select>
                      {errors.tipo_prova && (
                        <FieldError>{errors.tipo_prova}</FieldError>
                      )}
                    </Field>

                    {/* Período */}
                    <Field>
                      <FieldLabel htmlFor="periodo">Período *</FieldLabel>
                      <Input
                        id="periodo"
                        value={data.periodo}
                        onChange={(e) => setData('periodo', e.target.value)}
                        placeholder="Ex: 1º Trimestre"
                        disabled={processing}
                        required
                      />
                      {errors.periodo && (
                        <FieldError>{errors.periodo}</FieldError>
                      )}
                    </Field>

                    {/* Disciplina */}
                    <Field>
                      <FieldLabel htmlFor="disciplina_id">
                        Disciplina{' '}
                        <span className="text-xs font-normal text-muted-foreground">
                          (opcional)
                        </span>
                      </FieldLabel>
                      <Select
                        value={data.disciplina_id || 'todas'}
                        onValueChange={(value) =>
                          setData(
                            'disciplina_id',
                            value === 'todas' ? '' : value,
                          )
                        }
                        disabled={processing}
                      >
                        <SelectTrigger id="disciplina_id" className="w-full">
                          <SelectValue placeholder="Todas" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="todas">Todas</SelectItem>
                          {disciplinas?.map((d) => (
                            <SelectItem key={d.id} value={d.id}>
                              {d.nome}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      {errors.disciplina_id && (
                        <FieldError>{errors.disciplina_id}</FieldError>
                      )}
                    </Field>

                    {/* Classe */}
                    <Field>
                      <FieldLabel htmlFor="classe_id">
                        Classe{' '}
                        <span className="text-xs font-normal text-muted-foreground">
                          (opcional)
                        </span>
                      </FieldLabel>
                      <Select
                        value={data.classe_id || 'todas'}
                        onValueChange={(value) =>
                          setData('classe_id', value === 'todas' ? '' : value)
                        }
                        disabled={processing}
                      >
                        <SelectTrigger id="classe_id" className="w-full">
                          <SelectValue placeholder="Todas" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="todas">Todas</SelectItem>
                          {classes?.map((c) => (
                            <SelectItem key={c.id} value={c.id}>
                              {c.nome}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      {errors.classe_id && (
                        <FieldError>{errors.classe_id}</FieldError>
                      )}
                    </Field>

                    {/* Data Início */}
                    <Field>
                      <FieldLabel htmlFor="data_inicio">
                        Data de Início *
                      </FieldLabel>
                      <Input
                        id="data_inicio"
                        type="datetime-local"
                        value={data.data_inicio}
                        onChange={(e) =>
                          setData('data_inicio', e.target.value)
                        }
                        disabled={processing}
                        required
                      />
                      {errors.data_inicio && (
                        <FieldError>{errors.data_inicio}</FieldError>
                      )}
                    </Field>

                    {/* Data Limite */}
                    <Field>
                      <FieldLabel htmlFor="data_limite">
                        Data Limite *
                      </FieldLabel>
                      <Input
                        id="data_limite"
                        type="datetime-local"
                        value={data.data_limite}
                        onChange={(e) =>
                          setData('data_limite', e.target.value)
                        }
                        disabled={processing}
                        required
                      />
                      {errors.data_limite && (
                        <FieldError>{errors.data_limite}</FieldError>
                      )}
                    </Field>
                  </div>

                  {/* Observações */}
                  <Field>
                    <FieldLabel htmlFor="observacoes">
                      Observações{' '}
                      <span className="text-xs font-normal text-muted-foreground">
                        (opcional)
                      </span>
                    </FieldLabel>
                    <Textarea
                      id="observacoes"
                      rows={3}
                      value={data.observacoes}
                      onChange={(e) => setData('observacoes', e.target.value)}
                      disabled={processing}
                    />
                    {errors.observacoes && (
                      <FieldError>{errors.observacoes}</FieldError>
                    )}
                  </Field>

                  {/* Permite reenvio */}
                  <Field>
                    <div className="flex items-center gap-2">
                      <Switch
                        id="permiteReenvio"
                        checked={data.permite_reenvio}
                        onCheckedChange={(checked) =>
                          setData('permite_reenvio', checked)
                        }
                        disabled={processing}
                      />
                      <FieldLabel
                        htmlFor="permiteReenvio"
                        className="text-sm font-normal"
                      >
                        Permite reenvio
                      </FieldLabel>
                    </div>
                  </Field>
                </FieldSet>
              </FieldGroup>

              {/* Botões */}
              <div className="mt-6 flex flex-col-reverse gap-2 border-t pt-4 sm:flex-row sm:justify-end">
                <Button
                  type="button"
                  variant="outline"
                  asChild
                  disabled={processing}
                  className="w-full hover:cursor-pointer sm:w-auto"
                >
                  <Link href={`/dashboard/diretor/prazos/${prazo.id}`}>
                    Cancelar
                  </Link>
                </Button>
                <Button
                  type="submit"
                  disabled={processing}
                  className="w-full hover:cursor-pointer sm:w-auto"
                >
                  {processing ? (
                    <>
                      <Spinner />
                      Salvando...
                    </>
                  ) : (
                    'Guardar alterações'
                  )}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </>
  );
}